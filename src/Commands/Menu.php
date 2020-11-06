<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\dst_entity_generate\Services\GeneralApi;

/**
 * Class provides functionality of Menus generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Menu extends BaseEntityGenerate {
  /**
   * Google Sheet Api service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * DSTEG General service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $generalApi;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   General Api service definition.
   *   LoggerChannelFactory service definition.
   */
  public function __construct(GoogleSheetApi $sheet, GeneralApi $generalApi) {
    parent::__construct($sheet, $generalApi);
  }

  /**
   * Generate all the Drupal Menus from Drupal Spec tool sheet.
   *
   * @command dst:generate:menus
   * @aliases dst:m
   * @usage drush dst:generate:menus
   */
  public function generateMenus() {
    $result = FALSE;
    $skipEntitySync = $this->helper->skipEntitySync(DstegConstants::MENUS);
    $logMessages = [];
    if ($skipEntitySync) {
      $message = $this->t(DstegConstants::SKIP_ENTITY_MESSAGE,
      ['@entity' => DstegConstants::MENUS]);
      // @todo yell() and say() needs to be part of the `logMessage() method`.
      $this->yell($message, 100, 'yellow');
      $logMessages[] = $message;
      $result = CommandResult::exitCode(self::EXIT_SUCCESS);
    }
    if ($result === FALSE) {
      try {
        $this->yell($this->t('Generating Menus.'), 100, 'blue');
        $entity_data = $this->sheet->getData(DstegConstants::MENUS);
        if (!empty($entity_data)) {
          $menus_storage = $this->helper->getAllEntities('menu');
          foreach ($entity_data as $menu) {
            // Create menus only if it is in Wait and implement state.
            if ($menu['x'] === 'w') {
              $is_menu_present = $menus_storage
                ->load($menu['machine_name']);
              // Prevent exception if menu is already present.
              if (!isset($is_menu_present) || empty($is_menu_present)) {
                $is_saved = $menus_storage->create([
                  'id' => $menu['machine_name'],
                  'label' => $menu['title'],
                  'description' => $menu['description'],
                ])->save();
                if ($is_saved === 1) {
                  $success_message = $this->t('New menu @menu created.', [
                    '@menu' => $menu['title'],
                  ]);
                  $this->say($success_message);
                  $logMessages[] = $success_message;
                }
              }
              else {
                $present_message = $this->t('Skipping, Menu @menu already exists.', [
                  '@menu' => $menu['title'],
                ]);
                $this->say($present_message);
                $logMessages[] = $present_message;
              }
            }

          }
        }
        else {
          $no_data_message = $this->t('There is no data for the Menu entity in your DST sheet.');
          $this->say($no_data_message);
          $logMessages[] = $no_data_message;
        }
        $this->yell($this->t('Finished generating Menus.'), 100, 'blue');
        $result = CommandResult::exitCode(self::EXIT_SUCCESS);
      }
      catch (\Exception $exception) {
        $exception_message = $this->t('Exception occurred @exception', [
          '@exception' => $exception,
        ]);
        $this->yell($exception_message);
        $logMessages[] = $exception_message;
        $result = CommandResult::exitCode(self::EXIT_FAILURE);
      }
    }
    $this->helper->logMessage($logMessages);
    return $result;
  }

}
