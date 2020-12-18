<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class provides functionality of Vocabularies generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Vocabulary extends BaseEntityGenerate {

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(GoogleSheetApi $sheet, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, KeyValueFactoryInterface $key_value, ConfigFactoryInterface $config_factory, GeneralApi $generalApi) {
    parent::__construct($sheet, $generalApi);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->debugMode = $key_value->get('dst_entity_generate_storage')->get('debug_mode');
    $this->configFactory = $config_factory;
  }

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   */
  public function generateVocabularies() {
    $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    if ($sync_entities && array_key_exists('bundles', $sync_entities) && ($sync_entities['bundles']['Vocabularies'] === 'Vocabularies' || $sync_entities['bundles']['All'] === 'All')) {
      try {
        $this->say($this->t('Generating Drupal Vocabularies.'));
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
    else {
      $this->yell('Vocabulary sync is disabled, Skipping.');
    }
  }

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $command_result = self::EXIT_SUCCESS;
    $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $create_fields = $sync_entities['fields'];
    if ($create_fields['All'] === 'All') {
      $this->logger->notice($this->t('Generating Drupal Fields.'));
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
        foreach ($fields_data as $field) {
          $bundle = $field['bundle'];
          $bundle_name = trim(substr($bundle, 0, strpos($bundle, "(")));
          if (array_key_exists($bundle_name, $bundleArr)) {
            $bundleVal = $bundleArr[$bundle_name];
          }
          if (isset($bundleVal)) {
            if ($field['x'] === 'w') {
              try {
                $entity_type_id = 'taxonomy_vocabulary';
                $entity_type = 'taxonomy_term';
                $drupal_field = FieldConfig::loadByName($entity_type, $bundleVal, $field['machine_name']);

                // Skip field if present.
                if (!empty($drupal_field)) {
                  $this->logger->notice($this->t(
                    'The field @field is present in @vocab. Skipping.',
                    [
                      '@field' => $field['machine_name'],
                      '@vocab' => $bundleVal,
                    ]
                  ));
                  continue;
                }
                // Create field storage.
                $result = $this->helper->fieldStorageHandler($field, $entity_type);
                if ($result) {
                  $this->helper->addField($bundleVal, $field, $entity_type_id, $entity_type);
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

}
