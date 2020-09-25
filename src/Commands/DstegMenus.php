<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Drush command to generate menus.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegMenus extends DrushCommands {
  use StringTranslationTrait;
  /**
   * Google Sheet Api service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * Entity type manager service definition.
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
   * Config Factory service.
   *
   * @var array
   */
  protected $syncEntities;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   */
  public function __construct(GoogleSheetApi $googleSheetApi,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->googleSheetApi = $googleSheetApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
  }

  /**
   * Generate all the Drupal Menus from Drupal Spec tool sheet.
   *
   * @command dst:generate:menus
   * @aliases dst:mn
   * @usage drush dst:generate:menus
   */
  public function generateMenus() {
    if (!empty($this->syncEntities) && $this->syncEntities[strtolower(DstegConstants::MENUS)]['All'] !== 'All') {
      $skip_message = $this->t("Skipping Menus sync! It's disabled on general settings.");
      $this->say($skip_message);
      $this->logger->info($skip_message);
      return CommandResult::exitCode(self::EXIT_SUCCESS);
    }
    try {
      $this->say($this->t('Generating Drupal Menus.'));
      $menus_data = $this->googleSheetApi->getData(DstegConstants::MENUS);
      if (!empty($menus_data)) {
        $menus_storage = $this->entityTypeManager->getStorage('menu');
        foreach ($menus_data as $menu) {
          // Create menus only if it is in Wait and implement state.
          if ($menu['x'] === 'w') {
            $is_menu_present = $menus_storage
              ->load($menu['machine_name']);
            // Prevent exception if menu is already present.
            if (!isset($is_menu_present) || empty($is_menu_present)) {
              $is_saved = $this
                ->entityTypeManager
                ->getStorage('menu')
                ->create([
                  'id' => $menu['machine_name'],
                  'label' => $menu['title'],
                  'description' => $menu['description'],
                ])
                ->save();
              if ($is_saved === 1) {
                $success_message = $this->t('New menu @menu created.', [
                  '@menu' => $menu['title'],
                ]);
                $this->say($success_message);
                $this->logger->info($success_message);
              }
            }
            else {
              $present_message = $this->t('Menu @menu already present, Skipping.', [
                '@menu' => $menu['title'],
              ]);
              $this->say($present_message);
              $this->logger->info($present_message);
            }
          }
        }
      }
      return CommandResult::exitCode(self::EXIT_SUCCESS);
    }
    catch (\Exception $exception) {
      $this->yell($this->t('Exception occurred @exception', [
        '@exception' => $exception,
      ]));
      $this->logger->error('Exception occurred @exception', [
        '@exception' => $exception,
      ]);
      return CommandResult::exitCode(self::EXIT_FAILURE);
    }
  }

}
