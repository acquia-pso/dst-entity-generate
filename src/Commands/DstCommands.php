<?php

namespace Drupal\dst_entity_generate\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\StringTranslation\TranslationInterface;
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
   * DstCommands constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String Translation service variable.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    parent::__construct();
    $this->stringTranslation = $stringTranslation;
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

}
