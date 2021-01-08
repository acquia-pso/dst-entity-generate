<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\BaseEntityGenerate;

/**
 * Class provides functionality of supported entity generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class GenerateAll extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'all';

  /**
   * {@inheritdoc}
   */
  protected $dependentModules = [
    'workflows',
    'content_moderation',
    'media',
    'paragraphs',
  ];

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
    \system('drush dst:m');

    // Generate User Roles.
    \system('drush dst:ur');

    // Generate Image Styles.
    \system('drush dst:is');

    // Generate Image Effects.
    \system('drush dst:ie');

    // Generate Workflow.
    \system('drush dst:w');

    // Generate Vocabularies.
    \system('drush dst:v');

    // Generate Media.
    \system('drush dst:media');

    // Generate Paragraphs.
    \system('drush dst:p');

    // Generate Bundles.
    \system('drush dst:b');
  }

}
