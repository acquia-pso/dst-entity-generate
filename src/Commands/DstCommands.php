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
    }
    catch (\Exception $exception) {
      $this->yell($this->t('Exception occured @exception', [
        '@exception' => $exception,
      ]));
      $this->logger->error('Exception occured @exception', [
        '@exception' => $exception,
      ]);
    }

    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }
}
