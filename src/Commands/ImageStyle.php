<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;

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
   * DstCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate the Drupal image style from Drupal Spec tool sheet.
   *
   * @command dst:generate:imagestyle
   * @aliases dst:imagestyle dst:is
   * @usage drush dst:generate:imagestyle
   */
  public function generateImageStyle() {
    $this->io()->success('Generating Drupal Image Style...');
    $data = $this->getDataFromSheet(DstegConstants::IMAGE_STYLES);
    $image_styles = $this->getImageStyleData($data);
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');

    foreach ($image_styles as $image_style) {
      $name = $image_style['name'];
      if (!\is_null($image_style_storage->load($name))) {
        $this->io()->error("Image style $name already exists. Skipping creation...");
        continue;
      }
      $status = $image_style_storage->create($image_style)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Image Style $name is successfully created...");
      }
    }
  }

  /**
   * Get data needed for Image Style entity.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Image style compliant data.
   */
  private function getImageStyleData(array $data) {
    $image_styles = [];
    foreach ($data as $item) {
      $image_style = [];
      $image_style['label'] = $item['style_name'];
      $image_style['name'] = $item['machine_name'];
      \array_push($image_styles, $image_style);
    }
    return $image_styles;
  }

}
