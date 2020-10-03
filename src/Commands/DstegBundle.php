<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Helper;
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
   * @var \Drupal\dst_entity_generate\Helper
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
   * @param \Drupal\dst_entity_generate\Helper $dstegHelper
   *   The helper service for DSTEG.
   */
  public function __construct(GoogleSheetApi $sheet,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory,
                              ModuleHandlerInterface $moduleHandler,
                              EntityDisplayRepositoryInterface $displayRepository,
                              Helper $dstegHelper) {
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')
      ->get('sync_entities');
    $this->moduleHandler = $moduleHandler;
    $this->displayRepository = $displayRepository;
    $this->helper = $dstegHelper;
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
          $command_result = $this->helper->generateFields();
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

}
