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
   * @var \Drupal\dst_entity_generate\Commands\Bundle
   */
  protected $bundle;

  /**
   * Menu command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\Menu
   */
  protected $menus;

  /**
   * UserRole command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\UserRole
   */
  protected $userRoles;

  /**
   * ImageEffect command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\ImageEffect
   */
  protected $imageEffect;

  /**
   * Workflow command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\Workflow
   */
  protected $workflow;

  /**
   * ImageStyle command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\ImageStyle
   */
  protected $imageStyle;

  /**
   * DstegVocabulary command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\Vocabulary
   */
  protected $vocabulary;

  /**
   * Generate media command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\Media
   */
  protected $media;

  /**
   * Generate paragraph command definition.
   *
   * @var \Drupal\dst_entity_generate\Commands\Paragraph
   */
  protected $paragraph;

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
   * @param \Drupal\dst_entity_generate\Commands\Media $media
   *   Media generate command definition.
   * @param \Drupal\dst_entity_generate\Commands\Paragraph $paragraph
   *   Paragraph generate command definition.
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
                              ConfigFactoryInterface $configFactory,
                              Media $media,
                              Paragraph $paragraph) {
    parent::__construct($sheet, $generalApi, $configFactory);
    $this->stringTranslation = $stringTranslation;
    $this->bundle = $dstegBundle;
    $this->menus = $dstegMenus;
    $this->userRoles = $dstegUserRoles;
    $this->imageEffect = $dstegImageEffect;
    $this->workflow = $dstegWorkflows;
    $this->imageStyle = $dstegImageStyle;
    $this->vocabulary = $dstegVocabulary;
    $this->media = $media;
    $this->paragraph = $paragraph;
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

    // Generate Menus.
    $this->menus->generateMenus();

    // Generate User roles.
    $this->userRoles->generateUserRoles();

    // Generate image styles.
    $this->imageStyle->generateImageStyle();

    // Generate Image effects.
    $this->imageEffect->generateImageEffects();

    // Generate workflows.
    $this->workflow->generateWorkflows();

    // Generate vocabularies.
    $this->vocabulary->generateVocabularies();

    // Generate media types.
    $this->media->generateBundle();

    // Generate bundles.
    $this->bundle->generateBundle();

    // Generate paragraphs.
    $this->paragraph->generateParagraph();

    // Call all the methods to generate the Drupal entities.
    $this->io()->success('Congratulations. The entities which are enabled for sync are generated successfully.');

    return CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
