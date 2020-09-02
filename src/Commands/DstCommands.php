<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\Services\DstEntityGenerate;
use Drush\Commands\DrushCommands;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DstCommands.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * DstEntityGenerate service definition.
   *
   * @var DstEntityGenerate
   */
  protected $dstEntityGenerate;

  /**
   * DstCommands constructor.
   * @param DstEntityGenerate $dstEntityGenerate
   *   DstEntityGenerate service definition.
   */
  public function __construct(DstEntityGenerate $dstEntityGenerate) {
    parent::__construct();
    $this->dstEntityGenerate = $dstEntityGenerate;
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
    $this->say($this->t('Generating Drupal user roles.'));
    $is_generated = $this->dstEntityGenerate->generateUserRoles();
    if ($is_generated['success']) {
      $this->say('User roles created successfully.');
    }
    else {
      $this->say('Error in creating user roles.');
    }
    if (!empty($is_generated['message'])) {
      foreach ($is_generated['message'] as $message) {
        $this->say($message);
      }
    }
    return CommandResult::exitCode(self::EXIT_SUCCESS);
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
    $is_generated = $this->dstEntityGenerate->generateWorkflows();
    if ($is_generated['success']) {
      $this->say('Workflows created successfully.');
    }
    else {
      $this->say('Error in creating workflows.');
    }
    if (!empty($is_generated['message'])) {
      foreach ($is_generated['message'] as $message) {
        $this->say($message);
      }
    }
    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }
}
