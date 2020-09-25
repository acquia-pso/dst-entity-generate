<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
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
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:bundles
   * @aliases dst:generate:dst:generate:bundles dst:b
   * @usage drush dst:generate:bundles
   */
  public function generateBundle() {
    $this->say($this->t('Generating Drupal Content types.'));

    // Call all the methods to generate the Drupal entities.
    $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);

    if (!empty($bundles_data)) {
      $node_types_storage = $this->entityTypeManager->getStorage('node_type');
      foreach ($bundles_data as $bundle) {
        if ($bundle['type'] == 'Content type' && $bundle['x'] === 'w') {
          $ct = $node_types_storage->load($bundle['machine_name']);
          if ($ct == NULL) {
            $result = $node_types_storage->create([
              'type' => $bundle['machine_name'],
              'name' => $bundle['name'],
              'description' => $bundle['description'],
            ])->save();
            if ($result == SAVED_NEW) {
              $this->say($this->t('Content type @bundle is created.', ['@bundle' => $bundle['name']]));
            }
          }
          else {
            $this->say($this->t('Content type @bundle is already present.', ['@bundle' => $bundle['name']]));
          }
        }

      }

    }
    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
