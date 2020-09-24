<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\image\ImageEffectManager;
use Drush\Commands\DrushCommands;

/**
 * Class to provide functionality to generate Image effects.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegImageEffect extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GoogleSheetApi service class object.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $effectManager;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * DstegImageEffect constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\image\ImageEffectManager $effect_manager
   *   The image effect manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The Key Value Factory definition.
   */
  public function __construct(GoogleSheetApi $sheet, EntityTypeManagerInterface $entityTypeManager, ImageEffectManager $effect_manager, LoggerChannelFactoryInterface $loggerChannelFactory, KeyValueFactoryInterface $key_value) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->effectManager = $effect_manager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->debugMode = $key_value->get('dst_entity_generate_storage')->get('debug_mode');
  }

  /**
   * Generate Image effects from Drupal Spec tools sheet.
   *
   * @command dst:generate:image-effects
   * @aliases dst:ie
   * @usage dst:generate:image-effects
   */
  public function generateImageEffects() {
    $this->say($this->t('Generating Drupal Image Effects.'));
    $image_effects = $this->sheet->getData('Image effects');

    try {
      // Get all existing image effect plugin definitions.
      $image_effect_definitions = $this->effectManager->getDefinitions();
      // Get all existing image styles.
      $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();

      foreach ($image_effects as $image_effect) {
        if ($image_effect['x'] === 'w') {
          foreach ($image_styles as $image_style) {
            if ($image_style->label() === $image_effect['image_style']) {
              foreach ($image_effect_definitions as $image_effect_definition) {
                if ($image_effect_definition['label']->__toString() === $image_effect['effect']) {
                  $settings = $this->getConfigurationsFromSummery($image_effect['summary']);
                  if (!empty($settings)) {
                    $configuration = [
                      'uuid' => NULL,
                      'id' => $image_effect_definition['id'],
                      'weight' => 0,
                      'data' => $settings,
                    ];
                    $effect = $this->effectManager->createInstance($configuration['id'], $configuration);

                    $image_style->addImageEffect($effect->getConfiguration());
                    if ($image_style->save() == 2) {
                      $success_message = $this->t('Image effect @effect created in @style', [
                        '@effect' => $image_effect['effect'],
                        '@style' => $image_effect['image_style'],
                      ]);
                      $this->say($success_message);
                      $this->logger->info($success_message);
                    }
                  }
                  else {
                    $incomplete_requirement_message = $this->t('Please provide Image effect settings in Summery to create @effect effect in @style.', [
                      '@effect' => $image_effect['effect'],
                      '@style' => $image_effect['image_style'],
                    ]);
                    $this->say($incomplete_requirement_message);
                    $this->logger->info($incomplete_requirement_message);
                  }
                }
              }
            }
          }
        }
      }
      return CommandResult::exitCode(self::EXIT_SUCCESS);
    }
    catch (\Exception $exception) {
      if ($this->debugMode) {
        $exception_message = $this->t('Exception occurred @exception', [
          '@exception' => $exception->getMessage(),
        ]);
        $this->yell($exception_message);
        $this->logger->error($exception_message);
      }
      else {
        $exception_message = $this->t('Error occurred while processing image effects.');
        $this->yell($exception_message);
        $this->logger->error($exception_message);
      }
      return CommandResult::exitCode(self::EXIT_FAILURE);
    }
  }

  /**
   * Helper function to get cofigurations from summery.
   *
   * @param string $summery
   *   String containing image effect settings.
   *
   * @return array
   *   Image effect settings.
   */
  public function getConfigurationsFromSummery($summery) {
    $settings = [];
    // Summery pattern "W×H".
    $raw_config = preg_split("/(×|x|X)/", $summery);
    if (count($raw_config) == 2 && is_numeric($raw_config[0]) && is_numeric($raw_config[1])) {
      $settings['width'] = $raw_config[0];
      $settings['height'] = $raw_config[1];
      return $settings;
    }

    // Summery pattern "Width X Height X".
    $raw_config = explode(' ', $summery);
    for ($i = 0; $i < count($raw_config); $i++) {
      if (is_string($raw_config[$i]) && strtolower($raw_config[$i]) == 'width' && is_numeric($raw_config[$i + 1])) {
        $settings['width'] = $raw_config[$i + 1];
      }
      if (is_string($raw_config[$i]) && strtolower($raw_config[$i]) == 'height' && is_numeric($raw_config[$i + 1])) {
        $settings['height'] = $raw_config[$i + 1];
      }
    }
    return $settings;
  }

}
