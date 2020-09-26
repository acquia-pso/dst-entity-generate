<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;

/**
 * Drush Commands to generate workflows and its states from sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegWorkflows extends DrushCommands {
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
   * KeyValue service definition.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * ConfigFactory service definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DstEntityGenerate constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   KeyValueFactory service definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   ConfigFactory service definition.
   */
  public function __construct(GoogleSheetApi $googleSheetApi,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              KeyValueFactoryInterface $keyValueFactory,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->googleSheetApi = $googleSheetApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->keyValue = $keyValueFactory->get('dst_entity_generate_storage');
    $this->configFactory = $configFactory;
  }

  /**
   * Generate all the Drupal workflows from Drupal Spec tool sheet.
   *
   * @command dst:generate:workflow
   * @aliases dst:w
   * @usage drush dst:generate:workflow
   */
  public function generateWorkflows() {
    $this->say($this->t('Generating Drupal workflows.'));
    $default_weight = 0;
    $debug_mode = $this->keyValue->get('debug_mode');

    try {
      $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
      $is_import_workflow = $is_import_workflow_states = $is_import_workflow_transitions = FALSE;
      if ($sync_entities) {
        $is_import_workflow = ($sync_entities[DstegConstants::WORKFLOWS]['All'] === 'All');
        $is_import_workflow_states = ($sync_entities[DstegConstants::WORKFLOW_STATES]['All'] === 'All');
        $is_import_workflow_transitions = ($sync_entities[DstegConstants::WORKFLOW_TRANSITIONS]['All'] === 'All');
      }
      $workflow_storage = $this->entityTypeManager
        ->getStorage('workflow');
      if (isset($workflow_storage) && $is_import_workflow) {
        $google_sheet_api = $this->googleSheetApi;
        $workflow_map = [];

        // Get workflows from sheet and prepare a map.
        $workflows = $google_sheet_api->getData(DstegConstants::WORKFLOWS);
        foreach ($workflows as $workflow) {
          if ($workflow['x'] === 'w') {
            $workflow_map[$workflow['machine_name']] = $workflow['label'];
          }
        }

        // Get workflow states and transitions from sheet.
        $workflow_states = $google_sheet_api->getData(DstegConstants::WORKFLOW_STATES);
        $workflow_transitions = $google_sheet_api->getData(DstegConstants::WORKFLOW_TRANSITIONS);

        // Create workflow config with all states and transitions data.
        $workflow_config = [];
        $workflow_state_map = [];
        foreach ($workflow_map as $wf_machine_name => $wf_label) {
          $workflow_config['type_settings']['states'] = [];
          $workflow_config['type_settings']['transitions'] = [];

          if ($is_import_workflow_states) {
            // Add only non implemented work flow states.
            foreach ($workflow_states as $workflow_state) {
              if ($workflow_state['x'] === 'w' && $workflow_state['workflow'] === $wf_label) {
                $workflow_config['type_settings']['states'][$workflow_state['machine_name']] = [
                  'label' => $workflow_state['label'],
                  'weight' => $default_weight,
                ];
                $workflow_state_map[$workflow_state['machine_name']] = $workflow_state['label'];
              }
            }
          }

          if ($is_import_workflow_states && $is_import_workflow_transitions) {
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
          }

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
              $message = $this->t('New workflow @workflow created.', [
                '@workflow' => $wf_label,
              ]);
              $this->logger->info($message);
              $this->say($message);
            }
          }
        }
        return CommandResult::exitCode(self::EXIT_SUCCESS);
      }
      else {
        $message = $this->t('Workflow module is not installed.');
        if ($debug_mode) {
          $this->logger->error($message);
        }
        $this->say($message);
        return CommandResult::exitCode(self::EXIT_FAILURE);
      }
    }
    catch (\Exception $exception) {
      if ($debug_mode) {
        $this->logger->error('Exception occurred @exception.', [
          '@exception' => $exception,
        ]);
      }
      return CommandResult::exitCode(self::EXIT_FAILURE);
    }
  }

}
