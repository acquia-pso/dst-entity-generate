<?php

namespace Drupal\dst_entity_generate;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Helper class for entity generation.
 */
class Helper {

  use StringTranslationTrait;

  /**
   * Config array.
   *
   * @var array
   */
  protected $syncEntities;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The dst_entity_generate.google_sheet_api service.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Entity display mode repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * Constructs a Helper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $google_sheet_api
   *   The dst_entity_generate.google_sheet_api service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   Entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler,
                              EntityDisplayRepositoryInterface $entity_display_repository,
                              EntityTypeManagerInterface $entity_type_manager,
                              GoogleSheetApi $google_sheet_api,
                              LoggerChannelFactoryInterface $logger_factory,
                              EntityDisplayRepositoryInterface $displayRepository) {
    $this->moduleHandler = $module_handler;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->sheet = $google_sheet_api;
    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->syncEntities = $config_factory->get('dst_entity_generate.settings')
      ->get('sync_entities');
    $this->displayRepository = $displayRepository;
  }

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $command_result = TRUE;

    $create_fields = $this->syncEntities['fields'];
    if ($create_fields['All'] === 'All') {
      $this->logger->notice($this->t('Generating Drupal Fields.'));
      // Call all the methods to generate the Drupal entities.
      $fields_data = $this->sheet->getData(DstegConstants::FIELDS);

      $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);
      foreach ($bundles_data as $bundle) {
        if ($bundle['type'] === 'Content type') {
          $bundleArr[$bundle['name']] = $bundle['machine_name'];
        }
      }

      if (!empty($fields_data)) {
        foreach ($fields_data as $fields) {
          $bundleVal = '';
          $bundle = $fields['bundle'];
          $bundle_name = substr($bundle, 0, -15);
          if (array_key_exists($bundle_name, $bundleArr)) {
            $bundleVal = $bundleArr[$bundle_name];
          }

          // Skip fields which are not part of content type.
          if (!str_contains($fields['bundle'], 'Content type')) {
            continue;
          }

          if (isset($bundleVal)) {
            if ($fields['x'] === 'w') {
              try {
                // Deleting field.
                $drupal_field = FieldConfig::loadByName('node', $bundleVal, $fields['machine_name']);

                // Skip if field is present.
                if (!empty($drupal_field)) {
                  $this->logger->notice($this->t(
                    'The field @field is present in @ctype skipping.',
                    [
                      '@field' => $fields['machine_name'],
                      '@ctype' => $bundleVal,
                    ]
                  ));
                  continue;
                }

                // Check if field storage is present.
                $field_storage = FieldStorageConfig::loadByName('node', $fields['machine_name']);
                if (empty($field_storage)) {
                  // Create field storage.
                  switch ($fields['field_type']) {
                    case 'Text (plain)':
                      $fields['drupal_field_type'] = 'string';
                      $this->createFieldStorage($fields, 'node');
                      break;

                    case 'Text (formatted, long)':
                      $fields['drupal_field_type'] = 'text_long';
                      $this->createFieldStorage($fields, 'node');
                      break;

                    case 'Date':
                      $fields['drupal_field_type'] = 'datetime';
                      $this->createFieldStorage($fields, 'node');
                      break;

                    case 'Date range':
                      if ($this->moduleHandler->moduleExists('datetime_range')) {
                        $fields['drupal_field_type'] = 'daterange';
                        $this->createFieldStorage($fields, 'node');
                      }
                      else {
                        $this->logger->notice($this->t('The date range module is not installed. Skipping @field field generation.',
                          ['@field' => $fields['machine_name']]
                        ));
                        continue 2;
                      }
                      break;

                    case 'Link':
                      if ($this->moduleHandler->moduleExists('link')) {
                        $fields['drupal_field_type'] = 'link';
                        $this->createFieldStorage($fields, 'node');
                      }
                      else {
                        $this->logger->notice($this->t('The link module is not installed. Skipping @field field generation.',
                          ['@field' => $fields['machine_name']]
                        ));
                        continue 2;
                      }
                      break;

                    default:
                      $this->logger->notice($this->t('Support for generating field of type @ftype is currently not supported.',
                        ['@ftype' => $fields['field_type']]));
                      continue 2;
                  }

                  $this->logger->notice($this->t('Field storage created for @field',
                    ['@field' => $fields['machine_name']]
                  ));
                }

                $this->addField($bundleVal, $fields);
              }
              catch (\Exception $exception) {
                $this->logger->error($this->t('Error creating fields : @exception', [
                  '@exception' => $exception,
                ]));
                $command_result = FALSE;
              }
            }
          }
        }
      }
    }
    else {
      $this->logger->notice('Fields sync is disabled, Skipping.');
    }
    return $command_result;
  }

  /**
   * Create field storage helper function.
   *
   * @param array $field
   *   Field details.
   * @param string $entity_type
   *   Entity type.
   */
  protected function createFieldStorage(array $field, string $entity_type): void {
    $cardinality = $field['vals.'];
    if ($cardinality === '*') {
      $cardinality = -1;
    }
    elseif ($cardinality === '-') {
      $cardinality = 1;
    }
    FieldStorageConfig::create([
      'field_name' => $field['machine_name'],
      'entity_type' => $entity_type,
      'type' => $field['drupal_field_type'],
      'cardinality' => $cardinality,
    ])->save();
  }

  /**
   * Helper function to add field to content type.
   *
   * @param string $bundle_machine_name
   *   Content type machine name.
   * @param array $field_data
   *   Field data.
   */
  protected function addField(string $bundle_machine_name, array $field_data): void {
    $node_types_storage = $this->entityTypeManager->getStorage('node_type');
    $ct = $node_types_storage->load($bundle_machine_name);
    if ($ct != NULL) {

      $required = $field_data['req'] === 'y' ? TRUE : FALSE;
      // Create field instance.
      FieldConfig::create([
        'field_name' => $field_data['machine_name'],
        'entity_type' => 'node',
        'bundle' => $bundle_machine_name,
        'label' => $field_data['field_label'],
        'required' => $required,
      ])->save();

      // Set form display for new field.
      $this->displayRepository->getFormDisplay('node', $bundle_machine_name)
        ->setComponent($field_data['machine_name'],
          ['region' => 'content']
        )
        ->save();

      $this->logger->notice($this->t('@field field is created in content type @ctype',
        [
          '@field' => $field_data['machine_name'],
          '@ctype' => $bundle_machine_name,
        ]
      ));
    }
    else {
      $this->logger->notice($this->t('The @type content type does not exists.', ['@type' => $bundle_machine_name]));
    }
  }

}
