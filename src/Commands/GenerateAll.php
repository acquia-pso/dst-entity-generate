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
   *   Generates all entities.
   * @usage drush deg:generate --update
   *   Generates all entities and update existing.
   * @option update Update existing entity types with fields and creates new if not present.
   */
  public function generate($options = ['update' => FALSE]) {
    $this->io()->success('Generating All Drupal entities.');

    $commands = [
      'deg:m',
      'deg:ur',
      'deg:is',
      'deg:w',
      'deg:v',
      'deg:media',
      'deg:p',
      'deg:ct',
      'deg:cbt',
      'deg:ss',
    ];
    if ($options['update']) {
      $commands = array_map(function($command) { return $command . ' --update'; }, $commands);
    }
    // @todo Further refactor it so that we don't have to use exec function.
    foreach ($commands as $command) {
      \system('drush ' . $command);
    }
  }

}
