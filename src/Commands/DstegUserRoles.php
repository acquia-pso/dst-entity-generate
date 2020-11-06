<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;

/**
 * Drush command to generate user roles.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegUserRoles extends BaseEntityGenerate {

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
        $message = $this->t(DstegConstants::SKIP_ENTITY_MESSAGE,
          ['@entity' => DstegConstants::USER_ROLES]);
        $this->yell($message, 100, 'yellow');
        $logMessages[] = $message;
        $result = CommandResult::exitCode(self::EXIT_SUCCESS);
      }
      elseif (!$result) {
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
      $exception_message = $this->t('Exception occurred @exception', [
        '@exception' => $exception,
      ]);
      $this->yell($exception_message);
      $logMessages[] = $exception_message;
      $result = CommandResult::exitCode(self::EXIT_FAILURE);
    }

    $this->helper->logMessage($logMessages);
    return $result;
  }

}
