<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class provides functionality of workflows, states generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Workflow extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'workflows';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'workflows';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['workflows', 'content_moderation'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct the Workflow class object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate all the Drupal workflows from Drupal Spec tool sheet.
   *
   * @command dst:generate:workflow
   * @aliases dst:w
   * @usage drush dst:generate:workflow
   */
  public function generateWorkflows() {

    $this->io()->success('Generating Workflows...');

    // Get data fromm the DST Sheet.
    $data = $this->getDataFromSheet(DstegConstants::WORKFLOWS, FALSE);
    $workflows = $this->getWorkflowTypeData($data);
    $workflow_states = $this->getDataFromSheet(DstegConstants::WORKFLOW_STATES, FALSE);
    $workflow_transitions = $this->getDataFromSheet(DstegConstants::WORKFLOW_TRANSITIONS, FALSE);

    // Create the workflow config with all states and transitions data.
    $workflow_config = $workflow_state_map = [];
    $default_weight = 0;
    $workflow_storage = $this->entityTypeManager->getStorage('workflow');
    foreach ($workflows as $wf_data) {
      $wf_label = $wf_data['label'];
      $workflow_config['type_settings']['states'] = [];
      $workflow_config['type_settings']['transitions'] = [];

      // Add only non-implemented workflow states.
      foreach ($workflow_states as $workflow_state) {
        if ($workflow_state['workflow'] === $wf_label) {
          $workflow_config['type_settings']['states'][$workflow_state['machine_name']] = [
            'label' => $workflow_state['label'],
            'weight' => $default_weight,
          ];
          $workflow_state_map[$workflow_state['machine_name']] = $workflow_state['label'];
        }
      }

      // Add only non-implemented workflow transitions.

      foreach ($workflow_transitions as $workflow_transition) {
        if ($workflow_transition['workflow'] === $wf_label) {
          $workflow_transition_from[$workflow_transition['machine_name']][] = array_search(
            $workflow_transition['from_state'],
            $workflow_state_map
          );
          $workflow_transition_to = array_search($workflow_transition['to_state'], $workflow_state_map);
          if (empty($workflow_transition_from)) {
            $this->io()->warning("From states not present for workflow $wf_label");
            $this->io()->warning("Transitions " . $workflow_transition['label'] . " not created workflow $wf_label");
          }
          elseif (empty($workflow_transition_to)) {
            $this->io()->warning("To state " . $workflow_transition['to_state'] . " is not present for workflow $wf_label");
            $this->io()->warning("Transitions " . $workflow_transition['label'] . " not created workflow $wf_label");
          }
          else {
            $workflow_config['type_settings']['transitions'][$workflow_transition['machine_name']] = [
              'label' => $workflow_transition['label'],
              'from' => $workflow_transition_from[$workflow_transition['machine_name']],
              'to' => $workflow_transition_to,
              'weight' => $default_weight,
            ];
          }
        }
      }
      $wf_id = $wf_data['id'];
      if (!\is_null($wf_storage = $workflow_storage->load($wf_id))) {
        // Update the existing workflow with states and transitions.
        $this->io()->warning("$wf_label workflow already exists. Updating States & Transitions in case of any changes.");
        $type_settings = $wf_storage->get('type_settings');
        if (!empty($workflow_config['type_settings']['states'])) {
          $type_settings['states'] = array_merge($type_settings['states'], $workflow_config['type_settings']['states']);
        }
        if (!empty($workflow_config['type_settings']['transitions'])) {
          $type_settings['transitions'] = array_merge($type_settings['transitions'], $workflow_config['type_settings']['transitions']);
        }
        $wf_storage->set('type_settings', $type_settings);
        $wf_storage->save();
        continue;
      }
      // Create a new workflow.
      $workflow_config['id'] = $wf_id;
      $workflow_config['label'] = $wf_label;
      $workflow_config['type'] = 'content_moderation';
      $status = $workflow_storage->create($workflow_config)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("$wf_label workflow was created successfully...");
      }
    }
    $this->io->success('Finished generating workflows, states and transitions.');
  }

  /**
   * Get data needed for Workflows.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Workflow compliant data.
   */
  private function getWorkflowTypeData(array $data) {
    $workflow_types = [];
    foreach ($data as $item) {
      $workflow = [];
      $workflow['label'] = $item['label'];
      $workflow['id'] = $item['machine_name'];
      $workflow['type'] = $item['type'];
      \array_push($workflow_types, $workflow);
    }
    return $workflow_types;
  }

}
