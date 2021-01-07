<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\image\ImageEffectManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class provides functionality of Image effects generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class ImageEffect extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'image_effects';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['image'];

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $effectManager;

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DstegImageEffect constructor.
   *
   * @param \Drupal\image\ImageEffectManager $effect_manager
   *   The image effect manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   */
  public function __construct(ImageEffectManager $effect_manager, EntityTypeManagerInterface $entityTypeManager) {
    $this->effectManager = $effect_manager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate Image effects from Drupal Spec tools sheet.
   *
   * @command dst:generate:image-effects
   * @aliases dst:ie
   * @usage dst:generate:image-effects
   */
  public function generateImageEffects() {
    $this->io()->success('Generating Image Effects.');
    $image_effects = $this->getDataFromSheet(DstegConstants::IMAGE_EFFECTS);
    $image_styles = $this->getDatafromSheet(DstegConstants::IMAGE_STYLES);
    $image_styles = $this->convertToKeyValuePair($image_styles);
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');

    foreach ($image_effects as $image_effect) {
      $style = $image_styles[$image_effect['image_style']];
      $effect = $image_effect['effect'];
      if (is_null($image_style = $image_style_storage->load($style))) {
        $this->io()->error("Image style \"$style\" does not exist. Cannot add effect \"$effect\". Skipping...");
        continue;
      }
      $settings = $image_effect['summary'];
      $image_effect_config = $this->prepareImageEffectData($effect, $settings, $style);
      if (empty($image_effect_config)) {
        continue;
      }
      $image_effect_instance = $this->effectManager->createInstance($image_effect_config['id'], $image_effect_config);
      $image_style->addImageEffect($image_effect_instance->getConfiguration());
      $result = $image_style->save();
      if ($result) {
        $this->io()->success("Image Effect \"$effect\" successfully added to Image style \"$style\"");
      }

    }
  }

  /**
   * Generate Image effect data.
   *
   * @param string $effect
   *   Name of the image style where image effects will be added.
   * @param string $summary
   *   Image effect configuration string fetched from google sheet.
   * @param string $image_style
   *   Image style.
   *
   * @return array
   *   Image effect compliant data.
   */
  private function prepareImageEffectData(string $effect, string $summary, string $image_style) {
    $image_effect = [];
    $available_effects = $this->getAvailableImageEffects();
    if (!\in_array($effect, $available_effects)) {
      $this->io()->error("Image effect \"$effect\" can not be added to image style \"$image_style\" due to unavailability. Skipping...");
      return $image_effect;
    }

    $effect_id = \array_search($effect, $available_effects);
    $summary = $this->getConfigurationsFromSummary($summary);
    if (empty($summary)) {
      $this->io()->error("Image effect \"$effect\" can not be added to \"$image_style\" due to incompatible settings. Skipping...");
      return $image_effect;
    }
    $configuration = [
      'uuid' => NULL,
      'id' => $effect_id,
      'weight' => 0,
      'data' => $summary,
    ];
    return $configuration;
  }

  /**
   * Get available image effects from the system.
   *
   * @return array
   *   Available Image effects.
   */
  private function getAvailableImageEffects() {
    $image_effect_definitions = $this->effectManager->getDefinitions();
    $effects = [];
    foreach ($image_effect_definitions as $effect) {
      $effects[$effect['id']] = $effect['label']->__toString();
    }
    return $effects;
  }

  /**
   * Convert Image styles to keyValue pair of style name and machine name.
   *
   * @param array $data
   *   Image styles.
   *
   * @return array
   *   Key Value pair.
   */
  private function convertToKeyValuePair(array $data) {
    $key_value = [];
    foreach ($data as $item) {
      $key_value[$item['style_name']] = $item['machine_name'];
    }
    return $key_value;
  }

  /**
   * Helper function to get configurations from summary.
   *
   * @param string $summary
   *   String containing image effect settings.
   *
   * @return array
   *   Image effect settings.
   */
  private function getConfigurationsFromSummary($summary) {
    $settings = [];
    // Summary pattern "W×H".
    $raw_config = preg_split("/(×|x|X)/", $summary);
    if (count($raw_config) === 2 && is_numeric($raw_config[0]) && is_numeric($raw_config[1])) {
      $settings['width'] = $raw_config[0];
      $settings['height'] = $raw_config[1];
    }
    if (empty($settings)) {
      // Summary pattern "Width X Height X".
      $raw_config = explode(' ', $summary);
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
