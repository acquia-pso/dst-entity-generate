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
 * Drush command to generate fields.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegFields extends DrushCommands {

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
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
    $this->config = $configFactory->get('dst_entity_generate.settings');
  }

  /**
   * Generate the Drupal fields from Drupal Spec tool sheet.
   *
   * @command dst:generate:fields
   * @aliases dst:generate:dst:generate:fields dst:f
   * @usage drush dst:generate:fields
   */
  public function generateFields() {
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

                case 'Date range':
                  FieldStorageConfig::create([
                    'field_name' => $fields['machine_name'],
                    'entity_type' => 'node',
                    'type' => 'daterange',
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
              return CommandResult::exitCode(self::EXIT_FAILURE);
            }
          }
        }
      }

    }
    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
