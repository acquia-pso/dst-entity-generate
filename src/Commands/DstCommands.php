<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;
use Drupal\Core\StringTranslation\TranslationInterface;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstConstants;

/**
 * Class DstCommands.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * Google Sheet Api service definition.
   *
   * @var GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * Entity type manager service definition.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * DstCommands constructor.
   * @param TranslationInterface $stringTranslation
   *   Translation Interface definition.
   * @param GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service definition.
   */
  public function __construct(TranslationInterface $stringTranslation,
                              GoogleSheetApi $googleSheetApi,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    parent::__construct();
    $this->stringTranslation = $stringTranslation;
    $this->googleSheetApi = $googleSheetApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
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
   * Generate all the Drupal user roles from Drupal Spec tool sheet.
   *
   * @command dst:generate:menus
   * @aliases dst:mn
   * @usage drush dst:generate:menus
   */
  public function generateMenus() {
    try {
      $this->say($this->t('Generating Drupal menus.'));

      $menus_data = $this->googleSheetApi->getData(DstConstants::MENUS);
      if (!empty($menus_data)) {
        $menus_storage = $this->entityTypeManager->getStorage('menu');
        foreach($menus_data as $menu) {
          // Create role only if it is in Wait and implement state.
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
                $success_message = $this->t('New menu @menu created', [
                  '@menu' => $menu['title'],
                ]);
                $this->say($success_message);
                $this->logger->info($success_message);
              }
            }
            else {
              $present_message = $this->t('Menu @menu already present', [
                '@menu' => $menu['title'],
              ]);
              $this->say($present_message);
              $this->logger->info($present_message);
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      $this->yell($this->t('Exception occured @exception', [
        '@exception' => $exception,
      ]));
      //$this->logger->error('Exception occured @exception', [
       // '@exception' => $exception,
      //]);
    }

    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }
}

