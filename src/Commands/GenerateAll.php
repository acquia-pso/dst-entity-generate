<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;

/**
 * Class provides functionality of supported entity generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class GenerateAll extends BaseEntityGenerate {

  /**
   * Bundle command definition.
   *
   * @var Bundle
   */
  protected $dstegBundle;

  /**
   * Menu command definition.
   *
   * @var Menu
   */
  protected $dstegMenus;

  /**
   * UserRole command definition.
   *
   * @var UserRole
   */
  protected $dstegUserRoles;

  /**
   * ImageEffect command definition.
   *
   * @var ImageEffect
   */
  protected $dstegImageEffect;

  /**
   * Workflow command definition.
   *
   * @var Workflow
   */
  protected $dstegWorkflows;

  /**
   * ImageStyle command definition.
   *
   * @var ImageStyle
   */
  protected $dstegImageStyle;

  /**
   * DstegVocabulary command definition.
   *
   * @var Vocabulary
   */
  protected $dstegVocabulary;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   StringTranslation service definition.
   * @param Bundle $dstegBundle
   *   Bundle command definition.
   * @param Menu $dstegMenus
   *   DstegMenus command definition.
   * @param UserRole $dstegUserRoles
   *   DstegUserRoles command definition.
   * @param ImageEffect $dstegImageEffect
   *   ImageEffect command definition.
   * @param Workflow $dstegWorkflows
   *   DstegWorkflows command definition.
   * @param ImageStyle $dstegImageStyle
   *   DstegImageStyle command definition.
   * @param Vocabulary $dstegVocabulary
   *   Vocabulary command definition.
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(TranslationInterface $stringTranslation,
                              Bundle $dstegBundle,
                              Menu $dstegMenus,
                              UserRole $dstegUserRoles,
                              ImageEffect $dstegImageEffect,
                              Workflow $dstegWorkflows,
                              ImageStyle $dstegImageStyle,
                              Vocabulary $dstegVocabulary,
                              GoogleSheetApi $sheet,
                              GeneralApi $generalApi,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct($sheet, $generalApi, $configFactory);
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
    $this->yell($this->t('Congratulations. The entities which are enabled for sync are generated successfully.'));

    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
