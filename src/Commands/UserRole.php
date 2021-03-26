<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;

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
   * User Role constructor.
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
   * @options update Update existing entities.
   */
  public function generateUserRoles($options = ['update' => false]) {
    $this->io()->success('Generating Drupal User Roles...');
    $this->updateMode = $options['update'];
    $entity_data = $this->getDataFromSheet(DstegConstants::USER_ROLES, FALSE);
    if (!empty($entity_data)) {
      $user_role_data = $this->getUserRoleData($entity_data);
      $user_role_storage = $this->entityTypeManager->getStorage('user_role');
      $user_roles = $user_role_storage->loadMultiple();
      foreach ($user_role_data as $user_role) {
        $user_role_name = $user_role['label'];
        if ($user_roles[$user_role['id']]) {
          if ($this->updateMode) {
            $this->updateEntityType($user_roles[$user_role['id']], $user_role);
            $this->io()->success("User role $user_role_name updated.");
            continue;
          }
          $this->io()->warning("user_role $user_role_name Already exists. Skipping creation...");
          continue;
        }
        $status = $user_role_storage->create($user_role)->save();
        if ($status === SAVED_NEW) {
          $this->io()->success("user_role $user_role_name is successfully created...");
        }
      }
    }
    else {
      $this->io()->warning('There is no data for the user_role entity in your DST sheet.');
    }
  }

  /**
   * Get data needed for user role entity.
   *
   * @param array $data
   *   Array of user roles.
   *
   * @return array|null
   *   User Role compliant data.
   */
  private function getUserRoleData(array $data) {
    $user_roles = [];
    foreach ($data as $item) {
      $user_role = [];
      $user_role['id'] = $item['machine_name'];
      $user_role['label'] = $item['name'];
      \array_push($user_roles, $user_role);
    }
    return $user_roles;

  }

}
