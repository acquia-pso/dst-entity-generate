<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drush\Commands\DrushCommands;

/**
 * Drush commands class.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Google sheet.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translator trait.
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   Google Sheet.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(TranslationInterface $stringTranslation, GoogleSheetApi $sheet, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->stringTranslation = $stringTranslation;
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate
   * @aliases dst:generate:all dst:ga
   * @usage drush dst:generate
   */
  public function generate() {
    $this->say($this->t('Generating Drupal entities.'));
    // Call all the methods to generate the Drupal entities.
    $this->yell($this->t('Congratulations. All the Drupal entities are generated automatically.'));

    return CommandResult::exitCode(self::EXIT_SUCCESS);
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
    $fields_data = $this->sheet->getData("Fields");

    $bundles_data = $this->sheet->getData("Bundles");
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
          if ($fields['x'] === 'w' && $fields['field_type'] == 'Text (plain)') {

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

            FieldStorageConfig::create([
              'field_name' => $fields['machine_name'],
              'entity_type' => 'node',
              'type' => 'text',
            ])->save();

            FieldConfig::create([
              'field_name' => $fields['machine_name'],
              'entity_type' => 'node',
              'bundle' => $bundleVal,
              'label' => $fields['field_label'],
            ])->save();

          }
        }
      }

    }
    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
