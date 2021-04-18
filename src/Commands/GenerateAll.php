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
   * Generate all the Drupal entities from DEG sheet.
   *
   * @command deg:generate
   * @aliases deg:generate:all deg:ga
   * @usage drush deg:generate
   */
  public function generate() {
    $this->io()->success('Generating All Drupal entities.');

    // @todo Further refactor it so that we don't have to use exec fuynction.
    // Generate Menus.
    \system('drush deg:m');

    // Generate User Roles.
    \system('drush deg:ur');

    // Generate Image Styles.
    \system('drush deg:is');

    // Generate Workflow.
    \system('drush deg:w');

    // Generate Vocabularies.
    \system('drush deg:v');

    // Generate Media.
    \system('drush deg:media');

    // Generate Paragraphs.
    \system('drush deg:p');

    // Generate Content Types.
    \system('drush deg:ct');

    // Generate Custom Block Types.
    \system('drush deg:cbt');
  }

}
