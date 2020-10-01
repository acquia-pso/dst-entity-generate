<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drush\Commands\DrushCommands;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class to provide functionality to generate Vocabulary.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegVocabulary extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GoogleSheetApi service class object.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The Key Value Factory definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(GoogleSheetApi $sheet, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, KeyValueFactoryInterface $key_value, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->debugMode = $key_value->get('dst_entity_generate_storage')->get('debug_mode');
    $this->configFactory = $config_factory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   */
  public function generateVocabularies() {
    $this->say($this->t('Generating Drupal Vocabularies.'));
    $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    if ($sync_entities && array_key_exists('bundles', $sync_entities) && $sync_entities['bundles']['Vocabularies'] === 'Vocabularies') {
      try {
        $bundles = $this->sheet->getData(DstegConstants::BUNDLES);
        foreach ($bundles as $bundle) {
          if ($bundle['type'] === 'Vocabulary' && $bundle['x'] === 'w') {
            $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
            $vocabularies = $vocab_storage->loadMultiple();
            if (!isset($vocabularies[$bundle['machine_name']])) {
              $description = isset($bundle['description']) ? $bundle['description'] : $bundle['name'] . ' vocabulary.';
              $result = $vocab_storage->create([
                'vid' => $bundle['machine_name'],
                'description' => $description,
                'name' => $bundle['name'],
              ])->save();
              if ($result === 1) {
                $success_message = $this->t('Vocabulary @vocab is created.', [
                  '@vocab' => $bundle['name'],
                ]);
                $this->say($success_message);
                $this->logger->info($success_message);
              }
            }
            else {
              $present_message = $this->t('Vocabulary @vocab is already present.', [
                '@vocab' => $bundle['name'],
              ]);
              $this->say($present_message);
              $this->logger->info($present_message);
            }
          }
        }
        // Generate fields now.
        $command_result = $this->generateFields();
        return CommandResult::exitCode($command_result);
      }
      catch (\Exception $exception) {
        if ($this->debugMode) {
          $exception_message = $this->t('Exception occurred @exception.', [
            '@exception' => $exception->getMessage(),
          ]);
          $this->yell($exception_message);
          $this->logger->error($exception_message);
        }
        else {
          $exception_message = $this->t('Error occurred while processing Vocabularies.');
          $this->yell($exception_message);
          $this->logger->error($exception_message);
        }
        return CommandResult::exitCode(self::EXIT_FAILURE);
      }
    }
  }

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $command_result = self::EXIT_SUCCESS;
    $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $create_fields = $sync_entities['fields'];
    $this->say($this->t('Generating Drupal Fields.'));
    if ($create_fields['All'] === 'All') {
      $this->say($this->t('Generating Drupal Fields.'));
      // Call all the methods to generate the Drupal entities.
      $fields_data = $this->sheet->getData(DstegConstants::FIELDS);
      $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);
      $bundleArr = [];
      foreach ($bundles_data as $bundle) {
        if ($bundle['type'] === 'Vocabulary') {
          $bundleArr[$bundle['name']] = $bundle['machine_name'];
        }
      }
      if (!empty($fields_data)) {
        foreach ($fields_data as $fields) {
          $bundle = $fields['bundle'];
          $bundle_name = trim(substr($bundle, 0, strpos($bundle, "(")));
          if (array_key_exists($bundle_name, $bundleArr)) {
            $bundleVal = $bundleArr[$bundle_name];
          }
          if (isset($bundleVal)) {
            if ($fields['x'] === 'w') {
              try {
                // Deleting field.
                $field = FieldConfig::loadByName('taxonomy_vocabulary', $bundleVal, $fields['machine_name']);

                // Skip field if present.
                if (!empty($field)) {
                  $this->say($this->t(
                    'The field @field is present in @vocab skipping.',
                    [
                      '@field' => $fields['machine_name'],
                      '@vocab' => $bundleVal,
                    ]
                  ));
                  continue;
                }
                // Check if field storage is present.
                $field_storage = FieldStorageConfig::loadByName('taxonomy_vocabulary', $fields['machine_name']);
                if (empty($field_storage)) {
                  // Create field storage.
                  switch ($fields['field_type']) {
                    case 'Text (plain)':
                      $this->createFieldStorage($fields['machine_name'], 'taxonomy_term', 'string');
                      break;

                    case 'Text (formatted, long)':
                      $this->createFieldStorage($fields['machine_name'], 'taxonomy_term', 'text');
                      break;

                    case 'Date':
                      $this->createFieldStorage($fields['machine_name'], 'taxonomy_term', 'datetime');
                      break;

                    case 'Date range':
                      if ($this->moduleHandler->moduleExists('datetime_range')) {
                        $this->createFieldStorage($fields['machine_name'], 'taxonomy_term', 'daterange');
                      }
                      else {
                        $this->yell($this->t('The Date range module is not installed. Skipping @field field generation.',
                          ['@field' => $fields['machine_name']]
                        ));
                        continue 2;
                      }
                      break;

                    case 'Link':
                      if ($this->moduleHandler->moduleExists('link')) {
                        $this->createFieldStorage($fields['machine_name'], 'taxonomy_term', 'link');
                      }
                      else {
                        $this->yell($this->t('The Link module is not installed. Skipping @field field generation.',
                          ['@field' => $fields['machine_name']]
                        ));
                        continue 2;
                      }
                      break;

                    default:
                      $this->yell($this->t('Support for generating field of type @ftype is currently not supported.',
                        ['@ftype' => $fields['field_type']]));
                      continue 2;
                  }

                  $this->say($this->t('Field storage created for @field',
                    ['@field' => $fields['machine_name']]
                  ));
                }

                $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
                $vocab = $vocab_storage->load($bundleVal);
                if ($vocab != NULL) {
                  // Create field instance.
                  FieldConfig::create([
                    'field_name' => $fields['machine_name'],
                    'entity_type' => 'taxonomy_term',
                    'bundle' => $bundleVal,
                    'label' => $fields['field_label'],
                  ])->save();
                  $this->say($this->t('@field field is created in vocabulary @vocab.',
                    [
                      '@field' => $fields['machine_name'],
                      '@vocab' => $bundleVal,
                    ]
                  ));
                }
                else {
                  $this->say($this->t('The @vocab vocabulary does not exists.', ['@vocab' => $bundleVal]));
                }
              }
              catch (\Exception $exception) {
                $this->yell($this->t('Error creating fields : @exception', [
                  '@exception' => $exception,
                ]));
                $command_result = self::EXIT_FAILURE;
              }
            }
          }
        }
      }
    }
    else {
      $this->yell('Fields sync is disabled, Skipping.');
    }
    return CommandResult::exitCode($command_result);
  }

  /**
   * Create field storage helper function.
   *
   * @param string $field_machine_name
   *   Field machine name.
   * @param string $entity_type
   *   Entity type.
   * @param string $field_type
   *   Field type.
   */
  protected function createFieldStorage(string $field_machine_name, string $entity_type, string $field_type): void {
    FieldStorageConfig::create([
      'field_name' => $field_machine_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
    ])->save();
  }

}
