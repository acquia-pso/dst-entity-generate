<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * {@inheritDoc}
   */
  protected $dstEntityName = 'user_roles';

  /**
   * Machine name of entity which is going to import.
   *
   * @var string
   */
  protected $entity = '';

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DstegMenu constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate all the Drupal user roles from Drupal Spec tool sheet.
   *
   * @command dst:generate:user-roles
   * @aliases dst:ur
   * @usage drush dst:generate:user-roles
   */
  public function generateUserRoles() {
    $result = FALSE;
    $logMessages = [];
    try {
      $this->yell($this->t('Generating user roles.'), 100, 'blue');
      $user_role_data = $this->getDataFromSheet(DstegConstants::USER_ROLES);
      if (!empty($user_role_data)) {
        $user_role_storage = $this->entityTypeManager->getStorage('user_role');
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
    catch (\Exception $exception) {
      $this->displayAndLogException($exception, DstegConstants::USER_ROLES);
      $result = CommandResult::exitCode(self::EXIT_FAILURE);
    }

    return $result;
  }

}
