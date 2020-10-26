<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;

/**
 * Drush Commands to generate entities from sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstCommands extends BaseEntityGenerate {
  use StringTranslationTrait;

  /**
   * DstegBundle command definition.
   *
   * @var DstegBundle
   */
  protected $dstegBundle;

  /**
   * DstegMenus command definition.
   *
   * @var DstegMenus
   */
  protected $dstegMenus;

  /**
   * DstegUserRoles command definition.
   *
   * @var DstegUserRoles
   */
  protected $dstegUserRoles;

  /**
   * DstegImageEffect command definition.
   *
   * @var DstegImageEffect
   */
  protected $dstegImageEffect;

  /**
   * DstegWorkflows command definition.
   *
   * @var DstegWorkflows
   */
  protected $dstegWorkflows;

  /**
   * DstegImageStyle command definition.
   *
   * @var DstegImageStyle
   */
  protected $dstegImageStyle;

  /**
   * DstegVocabulary command definition.
   *
   * @var DstegVocabulary
   */
  protected $dstegVocabulary;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   StringTranslation service definition.
   * @param DstegBundle $dstegBundle
   *   DstegBundle command definition.
   * @param DstegMenus $dstegMenus
   *   DstegMenus command definition.
   * @param DstegUserRoles $dstegUserRoles
   *   DstegUserRoles command definition.
   * @param DstegImageEffect $dstegImageEffect
   *   DstegImageEffect command definition.
   * @param DstegWorkflows $dstegWorkflows
   *   DstegWorkflows command definition.
   * @param DstegImageStyle $dstegImageStyle
   *   DstegImageStyle command definition.
   * @param DstegVocabulary $dstegVocabulary
   *   DstegVocabulary command definition.
   */
  public function __construct(TranslationInterface $stringTranslation,
                              DstegBundle $dstegBundle,
                              DstegMenus $dstegMenus,
                              DstegUserRoles $dstegUserRoles,
                              DstegImageEffect $dstegImageEffect,
                              DstegWorkflows $dstegWorkflows,
                              DstegImageStyle $dstegImageStyle,
                              DstegVocabulary $dstegVocabulary) {
    parent::__construct();
    $this->stringTranslation = $stringTranslation;
    $this->dstegBundle = $dstegBundle;
    $this->dstegMenus = $dstegMenus;
    $this->dstegUserRoles = $dstegUserRoles;
    $this->dstegImageEffect = $dstegImageEffect;
    $this->dstegWorkflows = $dstegWorkflows;
    $this->dstegImageStyle = $dstegImageStyle;
    $this->dstegVocabulary = $dstegVocabulary;

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

    // Generate bundles.
    $this->dstegBundle->generateBundle();

    // Generate Menus.
    $this->dstegMenus->generateMenus();

    // Generate User roles.
    $this->dstegUserRoles->generateUserRoles();

    // Generate Image effects.
    $this->dstegImageEffect->generateImageEffects();

    // Generate workflows.
    $this->dstegWorkflows->generateWorkflows();

    // Generate image styles.
    $this->dstegImageStyle->generateImageStyle();

    // Generate vocabularies.
    $this->dstegVocabulary->generateVocabularies();

    // Call all the methods to generate the Drupal entities.
    $this->yell($this->t('Congratulations. All the Drupal entities are generated automatically.'));

    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
