<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;

/**
 * Drush command to generate user roles.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegUserRoles extends DrushCommands {
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
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    parent::__construct();
    $this->googleSheetApi = $googleSheetApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
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
      $this->say($this->t('Generating Drupal user roles.'));
      $user_role_data = $this->googleSheetApi->getData(DstConstants::USER_ROLES);
      if (!empty($user_role_data)) {
        $user_role_storage = $this->entityTypeManager->getStorage('user_role');
        foreach ($user_role_data as $user_role) {
          // Create role only if it is in Wait and implement state.
          if ($user_role['x'] === 'w') {
            $is_role_present = $user_role_storage
              ->load($user_role['machine_name']);
            // Prevent exception if role is already present.
            if (!isset($is_role_present) || empty($is_role_present)) {
              $is_saved = $this
                ->entityTypeManager
                ->getStorage('user_role')
                ->create([
                  'id' => $user_role['machine_name'],
                  'label' => $user_role['name'],
                ])
                ->save();
              if ($is_saved === 1) {
                $success_message = $this->t('New role @role created', [
                  '@role' => $user_role['name'],
                ]);
                $this->say($success_message);
                $this->logger->info($success_message);
              }
            }
            else {
              $present_message = $this->t('Role @role already present', [
                '@role' => $user_role['name'],
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
      $this->yell($this->t('Exception occured @exception', [
        '@exception' => $exception,
      ]));
      $this->logger->error('Exception occured @exception', [
        '@exception' => $exception,
      ]);
      return CommandResult::exitCode(self::EXIT_FAILURE);
    }
  }

}
