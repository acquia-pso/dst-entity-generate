<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\image\ImageEffectManager;

/**
 * Class provides functionality of Image effects generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class ImageEffect extends BaseEntityGenerate {

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $effectManager;

  /**
   * DstegImageEffect constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   General Api service definition.
   * @param \Drupal\image\ImageEffectManager $effect_manager
   *   The image effect manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(GoogleSheetApi $sheet,
                              GeneralApi $generalApi,
                              ImageEffectManager $effect_manager,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct($sheet, $generalApi, $configFactory);
    $this->effectManager = $effect_manager;
  }

  /**
   * Generate Image effects from Drupal Spec tools sheet.
   *
   * @command dst:generate:image-effects
   * @aliases dst:ie
   * @usage dst:generate:image-effects
   */
  public function generateImageEffects() {
    $result = FALSE;
    $logMessages = [];
    try {
      $this->yell($this->t('Generating Image Effects.'), 100, 'blue');
      $entity_data = $this->sheet->getData(DstegConstants::IMAGE_EFFECTS);
      if (!empty($entity_data)) {
        // Get all existing image effect plugin definitions.
        $image_effect_definitions = $this->effectManager->getDefinitions();
        // Get all existing image styles.
        $image_styles = $this->helper->getAllEntities('image_style', 'all');
        $any_matching_style = FALSE;
        foreach ($entity_data as $image_effect) {
          if ($image_effect['x'] === 'w') {
            foreach ($image_styles as $image_style) {
              if ($image_style->label() === $image_effect['image_style']) {
                $any_matching_style = TRUE;
                $any_matched_definition = FALSE;
                foreach ($image_effect_definitions as $image_effect_definition) {
                  if ($image_effect_definition['label']->__toString() === $image_effect['effect']) {
                    $any_matched_definition = TRUE;
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
                      $is_saved = $image_style->save();
                      if ($is_saved === 2) {
                        $success_message = $this->t('Image effect @effect created in @style', [
                          '@effect' => $image_effect['effect'],
                          '@style' => $image_effect['image_style'],
                        ]);
                        $this->say($success_message);
                        $logMessages[] = $success_message;
                      }
                      else {
                        $skip_message = $this->t('Skipping: Image effect @effect in @style', [
                          '@effect' => $image_effect['effect'],
                          '@style' => $image_effect['image_style'],
                        ]);
                        $this->say($skip_message);
                        $logMessages[] = $skip_message;
                      }
                    }
                    else {
                      $incomplete_requirement_message = $this->t('Please provide Image effect settings in Summery to create @effect effect in @style.', [
                        '@effect' => $image_effect['effect'],
                        '@style' => $image_effect['image_style'],
                      ]);
                      $this->say($incomplete_requirement_message);
                      $logMessages[] = $incomplete_requirement_message;
                    }
                  }
                }
                if ($any_matched_definition === FALSE) {
                  $no_match_message = $this->t('Skipping: No new matching Image effects found for the Image Style @style', [
                    '@style' => $image_effect['image_style'],
                  ]);
                  $this->say($no_match_message);
                  $logMessages[] = $no_match_message;
                }
              }
            }
          }
        }
        if ($any_matching_style === FALSE) {
          $no_style_match_message = $this->t('Skipping: There are no Image effects matching to any Image styles. Try running Image styles command first, i.e. drush dst:generate:imagestyle');
          $this->say($no_style_match_message);
          $logMessages[] = $no_style_match_message;
        }
      }
      else {
        $no_data_message = $this->t('There is no data for the Image effect entity in your DST sheet.');
        $this->say($no_data_message);
        $logMessages[] = $no_data_message;
      }
      $this->yell($this->t('Finished generating Image Effects.'), 100, 'blue');
      $result = CommandResult::exitCode(self::EXIT_SUCCESS);
    }
    catch (\Exception $exception) {
      $this->displayAndLogException($exception, DstegConstants::IMAGE_EFFECTS);
      $result = CommandResult::exitCode(self::EXIT_FAILURE);
    }
    $this->helper->logMessage($logMessages);
    return $result;
  }

  /**
   * Helper function to get configurations from summery.
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
    if (count($raw_config) === 2 && is_numeric($raw_config[0]) && is_numeric($raw_config[1])) {
      $settings['width'] = $raw_config[0];
      $settings['height'] = $raw_config[1];
    }
    if (empty($settings)) {
      // Summery pattern "Width X Height X".
      $raw_config = explode(' ', $summery);
      for ($i = 0; $i < count($raw_config); $i++) {
        if (is_string($raw_config[$i]) && strtolower($raw_config[$i]) === 'width' && is_numeric($raw_config[$i + 1])) {
          $settings['width'] = $raw_config[$i + 1];
        }
        if (is_string($raw_config[$i]) && strtolower($raw_config[$i]) === 'height' && is_numeric($raw_config[$i + 1])) {
          $settings['height'] = $raw_config[$i + 1];
        }
      }
    }
    return $settings;
  }

}
