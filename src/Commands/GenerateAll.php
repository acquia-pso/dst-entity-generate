<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drush\Commands\DrushCommands;

/**
 * Class provides functionality of supported entity generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class GenerateAll extends DrushCommands {

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate
   * @aliases dst:generate:all dst:ga
   * @usage drush dst:generate
   */
  public function generate() {
    $this->io()->success('Generating All Drupal entities.');

    // @todo Further refactor it so that we don't have to use exec fuynction.
    // Generate Menus.
    \exec('drush dst:m');

    // Generate User Roles.
    \exec('drush dst:ur');

    // Generate Image Styles.
    \exec('drush dst:is');

    // Generate Image Effects.
    \exec('drush dst:ie');

    // Generate Workflow.
    \exec('drush dst:w');

    // Generate Vocabularies.
    \exec('drush dst:v');

    // Generate Media.
    \exec('drush dst:media');

    // Generate Paragraphs.
    \exec('drush dst:p');

    // Generate Bundles.
    \exec('drush dst:b');
  }

}
