<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
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
   * DstegBundle constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   Google sheet.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(GoogleSheetApi $sheet,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory) {
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
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
                  'description' => empty($bundle['description'])?'':$bundle['description'],
                ])->save();
                if ($result === SAVED_NEW) {
                  $this->say($this->t('Content type @bundle is created.', ['@bundle' => $bundle['name']]));
                }
              }
              else {
                $this->say($this->t('Content type @bundle is already present, skipping.', ['@bundle' => $bundle['name']]));
              }
            }
          }
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
    // Generate fields now.
    $command_result = $this->generateFields();

    return CommandResult::exitCode($command_result);
  }

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $command_result = self::EXIT_SUCCESS;

    $create_fields = $this->syncEntities['fields'];
    if ($create_fields['All'] === 'All') {
      $this->say($this->t('Generating Drupal Fields.'));
      // Call all the methods to generate the Drupal entities.
      $fields_data = $this->sheet->getData(DstegConstants::FIELDS);

      $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);
      foreach ($bundles_data as $bundle) {
        $bundleArr[$bundle['name']] = $bundle['machine_name'];
      }
      if (!empty($fields_data)) {
        foreach ($fields_data as $fields) {
          $bundleVal = '';
          $bundle = $fields['bundle'];
          $bundle_name = substr($bundle, 0, -15);
          if (array_key_exists($bundle_name, $bundleArr)) {
            $bundleVal = $bundleArr[$bundle_name];
          }
          if (isset($bundleVal)) {
            if ($fields['x'] === 'w') {
              try {
                // Deleting field.
                $field = FieldConfig::loadByName('node', $bundleVal, $fields['machine_name']);
                if (!empty($field)) {
                  $field->delete();
                }

                // Deleting field storage.
                $field_storage = FieldStorageConfig::loadByName('node', $fields['machine_name']);
                if (!empty($field_storage)) {
                  $field_storage->delete();
                }

                // Create field storage.
                switch ($fields['field_type']) {
                  case 'Text (plain)':
                    FieldStorageConfig::create([
                      'field_name' => $fields['machine_name'],
                      'entity_type' => 'node',
                      'type' => 'string',
                    ])->save();
                    break;

                  case 'Text (formatted, long)':
                    FieldStorageConfig::create([
                      'field_name' => $fields['machine_name'],
                      'entity_type' => 'node',
                      'type' => 'text',
                    ])->save();
                    break;

                  case 'Date':
                    FieldStorageConfig::create([
                      'field_name' => $fields['machine_name'],
                      'entity_type' => 'node',
                      'type' => 'datetime',
                    ])->save();
                    break;
                }

                $node_types_storage = $this->entityTypeManager->getStorage('node_type');
                $ct = $node_types_storage->load($bundleVal);
                if ($ct != NULL) {
                  // Create field instance.
                  FieldConfig::create([
                    'field_name' => $fields['machine_name'],
                    'entity_type' => 'node',
                    'bundle' => $bundleVal,
                    'label' => $fields['field_label'],
                  ])->save();
                }
                else {
                  $this->say($this->t('The content type @type is no present.', ['@type' => $bundleVal]));
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
