<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\image\ImageEffectManager;
use Drupal\image\Entity\ImageStyle as CoreImageStyle;

/**
 * Class provides functionality of Image styles generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class ImageStyle extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'image_styles';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['image'];

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $effectManager;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\image\ImageEffectManager $effect_manager
   *   The image effect manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ImageEffectManager $effect_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->effectManager = $effect_manager;
  }

  /**
   * Generate the Drupal image style from Drupal Spec tool sheet.
   *
   * @command dst:generate:imagestyle
   * @aliases dst:imagestyle dst:is
   * @usage drush dst:generate:imagestyle
   * @options update Update existing entities.
   */
  public function generateImageStyle($options = ['update' => FALSE]) {
    $this->io()->success('Generating Drupal Image Style...');
    $this->updateMode = $options['update'];
    $data = $this->getDataFromSheet(DstegConstants::IMAGE_STYLES);
    $image_effects = $this->getDataFromSheet(DstegConstants::IMAGE_EFFECTS);
    $image_styles = $this->getImageStyleData($data, $image_effects);
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');

    foreach ($image_styles as $image_style) {
      $name = $image_style['name'];
      $image_style_entity = $image_style_storage->load($name);
      if (!\is_null($image_style)) {
        if ($this->updateMode) {
          $this->updateEntityType($image_style_entity, $image_style);
          $this->io()->success("Image style $name updated.");
        }
        else {
          $this->io()->warning("Image style $name already exists. Skipping creation...");
        }
        if (array_key_exists('effects', $image_style) && !empty($image_style['effects'])) {
          $this->generateImageEffects($image_style['effects'], $image_style_entity);
        }
        continue;
      }
      $image_style_entity = $image_style_storage->create([
        'label' => $image_style['label'],
        'name' => $image_style['name'],
      ]);
      $status = $image_style_entity->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Image Style $name is successfully created...");
      }
      if (array_key_exists('effects', $image_style) && !empty($image_style['effects'])) {
        $this->generateImageEffects($image_style['effects'], $image_style_entity);
      }
    }
  }

  /**
   * Get data needed for Image Style entity.
   *
   * @param array $styles
   *   Array of image styles.
   * @param array $effects
   *   Array of image effects.
   *
   * @return array|null
   *   Image style compliant data.
   */
  private function getImageStyleData(array $styles, array $effects) {
    $image_styles = [];
    foreach ($styles as $item) {
      $image_style = [];
      $image_style['label'] = $item['style_name'];
      $image_style['name'] = $item['machine_name'];
      foreach ($effects as $effect) {
        if ($item['style_name'] === $effect['image_style']) {
          $image_style['effects'][] = $effect;
        }
      }
      \array_push($image_styles, $image_style);
    }
    return $image_styles;
  }

  /**
   * Generate Image effects from Drupal Spec tools sheet.
   *
   * @param array $effects
   *   Array of image effects.
   * @param \Drupal\image\Entity\ImageStyle $image_style_entity
   *   Image style object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException|\Drupal\Core\Entity\EntityStorageException
   *   Throws entity or plugin exceptions.
   */
  public function generateImageEffects(array $effects, CoreImageStyle $image_style_entity) {
    $style_name = $image_style_entity->getName();
    foreach ($effects as $effect) {
      $effect_name = $effect['effect'];
      $image_effect_config = $this->prepareImageEffectData($effect_name, $effect['summary'], $style_name);
      if (empty($image_effect_config)) {
        continue;
      }
      $image_effect_instance = $this->effectManager->createInstance($image_effect_config['id'], $image_effect_config);
      $image_style_entity->addImageEffect($image_effect_instance->getConfiguration());
      $result = $image_style_entity->save();
      if ($result) {
        $this->io()->success("Image Effect \"$effect_name\" successfully added to Image style \"$style_name\"");
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
    return [
      'uuid' => NULL,
      'id' => $effect_id,
      'weight' => 0,
      'data' => $summary,
    ];
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
