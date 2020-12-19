<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;

/**
 * Class provides functionality of User roles generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class UserRole extends BaseEntityGenerate {

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   GeneralApi service definition.
   */
  public function __construct(GoogleSheetApi $googleSheetApi,
                              GeneralApi $generalApi) {
    parent::__construct($googleSheetApi, $generalApi);
  }

  /**
   * Generate all the Drupal user roles from Drupal Spec tool sheet.
   *
   * @command dst:generate:user-roles
   * @aliases dst:ur
   * @usage drush dst:generate:user-roles
   */
  public function generateUserRoles() {
    try {
      $result = FALSE;
      $skipEntitySync = $this->helper->skipEntitySync(DstegConstants::USER_ROLES);
      $logMessages = [];
      if ($skipEntitySync) {
        $result = $this->displaySkipMessage(DstegConstants::CONTENT_TYPES);
      }
      if ($result === FALSE) {
        $this->yell($this->t('Generating user roles.'), 100, 'blue');
        $user_role_data = $this->sheet->getData(DstegConstants::USER_ROLES);
        if (!empty($user_role_data)) {
          $user_role_storage = $this->helper->getAllEntities('user_role');
          foreach ($user_role_data as $user_role) {
            // Create role only if it is in Wait and implement state.
            if ($user_role['x'] === 'w') {
              $is_role_present = $user_role_storage
                ->load($user_role['machine_name']);
              // Prevent exception if role is already present.
              if (!isset($is_role_present) || empty($is_role_present)) {
                $is_saved = $user_role_storage
                  ->create([
                    'id' => $user_role['machine_name'],
                    'label' => $user_role['name'],
                  ])
                  ->save();
                if ($is_saved === 1) {
                  $success_message = $this->t('New role @role created.', [
                    '@role' => $user_role['name'],
                  ]);
                  $this->say($success_message);
                  $logMessages[] = $success_message;
                }
              }
              else {
                $present_message = $this->t('Role @role already present.', [
                  '@role' => $user_role['name'],
                ]);
                $this->say($present_message);
                $logMessages[] = $present_message;
              }
            }
          }
        }
        else {
          $no_data_message = $this->t('There is no data for the User role entity in your DST sheet.');
          $this->say($no_data_message);
          $logMessages[] = $no_data_message;
        }
        $this->yell($this->t('Finished generating User roles.'), 100, 'blue');
        $result = CommandResult::exitCode(self::EXIT_SUCCESS);
      }
    }
    catch (\Exception $exception) {
      $this->displayAndLogException($exception, DstegConstants::USER_ROLES);
      $result = CommandResult::exitCode(self::EXIT_FAILURE);
    }

    $this->helper->logMessage($logMessages);
    return $result;
  }

}
