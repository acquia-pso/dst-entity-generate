<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstConstants;

/**
 * Class DstEntityGenerate.
 *
 * @package Drupal\dst_entity_generate\Services
 */
class DstEntityGenerate {
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
   * DstEntityGenerate constructor.
   * @param TranslationInterface $stringTranslation
   *   Translation Interface definition.
   * @param GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service definition.
   * @param LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   */
  public function __construct(TranslationInterface $stringTranslation,
                              GoogleSheetApi $googleSheetApi,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->stringTranslation = $stringTranslation;
    $this->googleSheetApi = $googleSheetApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
  }


  /**
   * Generate User Roles.
   *
   * @return array
   *   Array with success status and message.
   */
  public function generateUserRoles() {
    $success = TRUE;
    $message = [];
    try {
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
                $message[] = $this->t('New role @role created', [
                  '@role' => $user_role['name'],
                ]);
                $this->logger->info(end($message));
              }
            }
            else {
              $success = FALSE;
              $message[] = $this->t('Role @role already present', [
                '@role' => $user_role['name'],
              ]);
              $this->logger->info(end($message));
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      $success = FALSE;
      $this->logger->error('Exception occured @exception', [
        '@exception' => $exception,
      ]);
    }

    return [
      'success' => $success,
      'message' => $message,
    ];
  }

  /**
   * Generate Workflows.
   *
   * @return array
   *   Array with success status and message.
   */
  public function generateWorkflows() {
    $success = TRUE;
    $message = [];
    $default_weight = 0;
    try {
      $google_sheet_api = $this->googleSheetApi;
      $workflow_map = [];

      // Get workflows from sheet and prepare a map.
      $workflows = $google_sheet_api->getData(DstConstants::WORKFLOWS);
      foreach ($workflows as $workflow) {
        if ($workflow['x'] === 'w') {
          $workflow_map[$workflow['machine_name']] = $workflow['label'];
        }
      }

      // Get workflow states and transitions from sheet.
      $workflow_states = $google_sheet_api->getData('Workflow states');
      $workflow_transitions = $google_sheet_api->getData('Workflow transitions');

      // Create workflow config with all states and transtions data.
      $workflow_config = [];
      $workflow_state_map = [];
      foreach ($workflow_map as $wf_machine_name => $wf_label) {
        $workflow_config['type_settings']['states'] = [];
        $workflow_config['type_settings']['transitions'] = [];

        // Add only non implmented work flow states.
        foreach ($workflow_states as $workflow_state) {
          if ($workflow_state['x'] === 'w' && $workflow_state['workflow'] === $wf_label) {
            $workflow_config['type_settings']['states'][$workflow_state['machine_name']] = [
              'label' => $workflow_state['label'],
              'weight' => $default_weight,
            ];
            $workflow_state_map[$workflow_state['machine_name']] = $workflow_state['label'];
          }
        }

        // Add only non implemented work flow transitions.
        foreach ($workflow_transitions as $workflow_transition) {
          if ($workflow_transition['x'] === 'w' && $workflow_transition['workflow'] === $wf_label) {
            // Create transition from array.
            $workflow_transition_from[$workflow_transition['machine_name']][] = array_search(
              $workflow_transition['from_state'],
              $workflow_state_map
            );
            $workflow_config['type_settings']['transitions'][$workflow_transition['machine_name']] = [
              'label' => $workflow_transition['label'],
              'from' => $workflow_transition_from[$workflow_transition['machine_name']],
              'to' => array_search($workflow_transition['to_state'], $workflow_state_map),
              'weight' => $default_weight,
            ];
          }
        }

        $workflow_storage = $this->entityTypeManager
          ->getStorage('workflow');
        if (isset($workflow_storage)) {
          $is_workflow_present = $workflow_storage->load($wf_machine_name);
          if (isset($is_workflow_present) || !empty($is_workflow_present)) {
            // Set states and transitions if workflow is already present.
            $type_settings = $is_workflow_present->get('type_settings');
            if (!empty($workflow_config['type_settings']['states'])) {
              $type_settings['states'] = array_merge($type_settings['states'], $workflow_config['type_settings']['states']);
            }
            if (!empty($workflow_config['type_settings']['transitions'])) {
              $type_settings['transitions'] = array_merge($type_settings['transitions'], $workflow_config['type_settings']['transitions']);
            }
            $is_workflow_present->set('type_settings', $type_settings);
            $is_workflow_present->save();
          }
          else {
            // Create new workflow.
            $workflow_config['id'] = $wf_machine_name;
            $workflow_config['label'] = $wf_label;
            $workflow_config['type'] = 'content_moderation';
            $is_workflow_saved = $workflow_storage
              ->create($workflow_config)
              ->save();
            if ($is_workflow_saved === 1) {
              $message[] = $this->t('New workflow @workflow created', [
                '@workflow' => $wf_label,
              ]);
              $this->logger->info(end($message));
            }
          }
        }
        else {
          $success = FALSE;
          $message[] = $this->t('Workflow module not enabled.');
          $this->logger->error(end($message));
        }
      }
    }
    catch (\Exception $exception) {
      $success = FALSE;
      $this->logger->error('Exception occurred @exception', [
        '@exception' => $exception,
      ]);
    }

    return [
      'success' => $success,
      'message' => $message,
    ];
  }
}
