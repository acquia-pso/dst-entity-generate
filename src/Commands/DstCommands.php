<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\taxonomy\Entity\Vocabulary;
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

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   */
  public function generateVocabularies() {
    // Once all dependency injection code merged, remove static service call.
    // @todo: Replace static service calls with DIs.
    $api = \Drupal::service('dst_entity_generate.google_sheet');
    $bundles = $api->getData('Bundles');
    foreach ($bundles as $bundle) {
      if ($bundle['type'] == 'Vocabulary' && $bundle['x'] === 'w') {
        $vocabularies = Vocabulary::loadMultiple();
        if (!isset($vocabularies[$bundle['machine_name']])) {
          $vocabulary = Vocabulary::create(array(
            'vid' => $bundle['machine_name'],
            'description' => isset($bundle['description']) ? $bundle['description'] : '',
            'name' => $bundle['name'],
          ));
          $vocabulary->save();
          $success_message = $this->t('Vocabulary @entity created.', [
            '@entity' => $bundle['name'],
          ]);
          $this->say($success_message);
          // $this->logger->info($success_message);
        }
        else {
          $present_message = $this->t('Vocabulary @entity already exist.', [
            '@entity' => $bundle['name'],
          ]);
          $this->say($present_message);
          // $this->logger->info($present_message);
        }
      }
    }
  }

}
