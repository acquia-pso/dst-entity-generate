<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drush\Commands\DrushCommands;

/**
 * Drush command to generate content types.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegBundle extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Google sheet service.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Config array.
   *
   * @var array
   */
  protected $syncEntities;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity display mode repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * Helper class for entity generation.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $helper;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   Google sheet.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   Display mode repository.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(GoogleSheetApi $sheet,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory,
                              ModuleHandlerInterface $moduleHandler,
                              EntityDisplayRepositoryInterface $displayRepository,
                              GeneralApi $generalApi) {
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')
      ->get('sync_entities');
    $this->moduleHandler = $moduleHandler;
    $this->displayRepository = $displayRepository;
    $this->helper = $generalApi;
  }

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:bundles
   * @aliases dst:generate:dst:generate:bundles dst:b
   * @usage drush dst:generate:bundles
   */
  public function generateBundle() {
    $command_result = self::EXIT_SUCCESS;

    $create_ct = $this->syncEntities['bundles'];
    if ($create_ct['All'] === 'All' || $create_ct['Content types'] === 'Content types') {
      $this->say($this->t('Generating Drupal Content types.'));

      // Call all the methods to generate the Drupal entities.
      $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);

      if (!empty($bundles_data)) {
        try {
          $node_types_storage = $this->entityTypeManager->getStorage('node_type');
          foreach ($bundles_data as $bundle) {
            if ($bundle['type'] === 'Content type' && $bundle['x'] === 'w') {
              $ct = $node_types_storage->load($bundle['machine_name']);
              if ($ct === NULL) {
                $result = $node_types_storage->create([
                  'type' => $bundle['machine_name'],
                  'name' => $bundle['name'],
                  'description' => empty($bundle['description']) ? $bundle['name'] . ' content type' : $bundle['description'],
                ])->save();
                if ($result === SAVED_NEW) {
                  $this->say($this->t('Content type @bundle is created.', ['@bundle' => $bundle['name']]));
                }

                // Create display modes for newly created content type.
                // Assign widget settings for the default form mode.
                $this->displayRepository->getFormDisplay('node', $bundle['machine_name'])
                  ->save();

                // Assign display settings for the display view modes.
                $this->displayRepository->getViewDisplay('node', $bundle['machine_name'])
                  ->save();
              }
              else {
                $this->say($this->t('Content type @bundle is already present, skipping.', ['@bundle' => $bundle['name']]));
              }
            }
          }

          // Generate fields now.
          $command_result = $this->generateFields();
        }
        catch (\Exception $exception) {
          $this->yell($this->t('Error creating content type : @exception', [
            '@exception' => $exception,
          ]));
          $command_result = self::EXIT_FAILURE;
        }
      }
    }
    else {
      $this->yell('Content type sync is disabled, Skipping.');
    }

    return CommandResult::exitCode($command_result);
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
                $entity_type_id = 'node_type';
                $entity_type = 'node';
                $drupal_field = FieldConfig::loadByName($entity_type_id, $bundleVal, $fields['machine_name']);

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
                $field_storage = FieldStorageConfig::loadByName($entity_type_id, $fields['machine_name']);
                if (empty($field_storage)) {
                  // Create field storage.
                  switch ($fields['field_type']) {
                    case 'Text (plain)':
                      $fields['drupal_field_type'] = 'string';
                      $this->helper->createFieldStorage($fields, $entity_type);
                      break;

                    case 'Text (formatted, long)':
                      $fields['drupal_field_type'] = 'text_long';
                      $this->helper->createFieldStorage($fields, $entity_type);
                      break;

                    case 'Date':
                      $fields['drupal_field_type'] = 'datetime';
                      $this->helper->createFieldStorage($fields, $entity_type);
                      break;

                    case 'Date range':
                      if ($this->moduleHandler->moduleExists('datetime_range')) {
                        $fields['drupal_field_type'] = 'daterange';
                        $this->helper->createFieldStorage($fields, $entity_type);
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
                        $this->helper->createFieldStorage($fields, $entity_type);
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

                $this->helper->addField($bundleVal, $fields, $entity_type_id, $entity_type);
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

}
