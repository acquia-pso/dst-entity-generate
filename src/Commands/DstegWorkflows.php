<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
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
   * GeneralApi service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $generalApi;

  /**
   * DstEntityGenerate constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   GeneralApi service definition.
   */
  public function __construct(GoogleSheetApi $googleSheetApi,
                              GeneralApi $generalApi) {
    parent::__construct();
    $this->googleSheetApi = $googleSheetApi;
    $this->generalApi = $generalApi;
  }

  /**
   * Generate all the Drupal workflows from Drupal Spec tool sheet.
   *
   * @command dst:generate:workflow
   * @aliases dst:w
   * @usage drush dst:generate:workflow
   */
  public function generateWorkflows() {
    $workflow_enabled = $this->generalApi->isModuleEnabled('workflows');
    $content_moderation_enabled = $this->generalApi->isModuleEnabled('content_moderation');

    try {
      $is_import_workflow = (!$this->generalApi->skipEntitySync(DstegConstants::WORKFLOWS));
      $is_import_workflow_states = (!$this->generalApi->skipEntitySync(DstegConstants::WORKFLOW_STATES));
      $is_import_workflow_transitions = (!$this->generalApi->skipEntitySync(DstegConstants::WORKFLOW_TRANSITIONS));

      if (!$workflow_enabled) {
        $this->showMessageOnCli($this->t('Please install workflows module.'));
      }
      elseif (!$content_moderation_enabled) {
        $this->showMessageOnCli($this->t('Please install content moderation module.'));
      }
      elseif (!$is_import_workflow) {
        $this->showMessageOnCli($this->t('Please enable Workflow All in General Configurations.'));
      }
      else {
        $default_weight = 0;
        $this->yell($this->t('Generating Workflows.'), 100, 'blue');
        $workflow_storage = $this->generalApi->getAllEntities('workflow');
        $google_sheet_api = $this->googleSheetApi;
        $workflow_map = [];

        // Get workflows from the sheet and prepare a map.
        $workflows = $google_sheet_api->getData(DstegConstants::WORKFLOWS);
        foreach ($workflows as $workflow) {
          if ($workflow['x'] === 'w') {
            $workflow_map[$workflow['machine_name']] = $workflow['label'];
          }
        }

        // Get workflow states and transitions from the sheet.
        $workflow_states = $google_sheet_api->getData(DstegConstants::WORKFLOW_STATES);
        $workflow_transitions = $google_sheet_api->getData(DstegConstants::WORKFLOW_TRANSITIONS);

        // Create the workflow config with all states and transitions data.
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
                $this->say($this->t('State @wf_state created successfully for workflow @workflow', [
                  '@wf_state' => $workflow_state['label'],
                  '@workflow' => $wf_label,
                ]));
              }
            }
          }

          if ($is_import_workflow_states && $is_import_workflow_transitions) {
            // Add only non implemented work flow transitions.
            foreach ($workflow_transitions as $workflow_transition) {
              if ($workflow_transition['x'] === 'w' && $workflow_transition['workflow'] === $wf_label) {
                // Create transition from the array.
                $workflow_transition_from[$workflow_transition['machine_name']][] = array_search(
                  $workflow_transition['from_state'],
                  $workflow_state_map
                );
                $workflow_transition_to = array_search($workflow_transition['to_state'], $workflow_state_map);
                if (empty($workflow_transition_from)) {
                  $this->showMessageOnCli($this->t('From states not present for workflow @workflow', [
                    '@workflow' => $wf_label,
                  ]));
                  $this->showMessageOnCli($this->t('Transitions @wf_trans not created workflow @workflow', [
                    '@wf_trans' => $workflow_transition['label'],
                    '@workflow' => $wf_label,
                  ]));
                }
                elseif (empty($workflow_transition_to)) {
                  $this->showMessageOnCli($this->t('To state @to_state is not present for workflow @workflow', [
                    '@to_state' => $workflow_transition['to_state'],
                    '@workflow' => $wf_label,
                  ]));
                  $this->showMessageOnCli($this->t('Transitions @wf_trans not created for workflow @workflow', [
                    '@wf_trans' => $workflow_transition['label'],
                    '@workflow' => $wf_label,
                  ]));
                }
                else {
                  $workflow_config['type_settings']['transitions'][$workflow_transition['machine_name']] = [
                    'label' => $workflow_transition['label'],
                    'from' => $workflow_transition_from[$workflow_transition['machine_name']],
                    'to' => $workflow_transition_to,
                    'weight' => $default_weight,
                  ];
                  $this->showMessageOnCli($this->t('Transitions @wf_trans created successfully for workflow @workflow', [
                    '@wf_trans' => $workflow_transition['label'],
                    '@workflow' => $wf_label,
                  ]));
                }
              }
            }
          }

          $is_workflow_present = $workflow_storage->load($wf_machine_name);
          if (isset($is_workflow_present) || !empty($is_workflow_present)) {
            // Set states and transitions if workflow is present.
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
            // Create a new workflow.
            $workflow_config['id'] = $wf_machine_name;
            $workflow_config['label'] = $wf_label;
            $workflow_config['type'] = 'content_moderation';
            $is_workflow_saved = $workflow_storage
              ->create($workflow_config)
              ->save();
            if ($is_workflow_saved === 1) {
              $this->showMessageOnCli($this->t('New workflow @workflow created along with states and transitions.', [
                '@workflow' => $wf_label,
              ]));
            }
          }
        }
      }
      $this->yell($this->t('Finished generating workflows, states and transitions.'), 100, 'blue');
      $command_result = self::EXIT_SUCCESS;
    }
    catch (\Exception $exception) {
      $this->say('Exception occurred while import.');
      $this->yell($exception);
      $this->generalApi->logMessage(['Exception occurred @exception', [
        '@exception' => $exception,
      ],
      ]
          );
      $command_result = self::EXIT_FAILURE;
    }
    return CommandResult::exitCode($command_result);
  }

  /**
   * Helper function to say message on cli as well log them.
   *
   * @param string $message
   *   The translated message string.
   */
  private function showMessageOnCli(string $message) {
    $this->generalApi->logMessage([$message]);
    $this->say($message);
  }

}
