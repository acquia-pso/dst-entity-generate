<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Providees functionality for creating Cohesion font stack from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class SiteStudioSettings extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'site_studio_settings';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['cohesion', 'cohesion_website_settings'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A service for generating UUIDs.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * List of required fields to create entity.
   *
   * @var array
   */
  protected $requiredFields = ['label', 'machine_name'];

  /**
   * Content type generator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   A service for generating UUIDs.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UuidInterface $uuid) {
    $this->entityTypeManager = $entityTypeManager;
    $this->uuid = $uuid;
  }

  /**
   * Generate all the Drupal content types from DEG sheet.
   *
   * @command deg:generate:site-studio
   * @aliases deg:ss $type
   * @usage drush deg:ss color
   *   Generates site studio fonts if not present.
   * @usage drush deg:ss --update
   *   Generate site studio fonts if not present also updates existing.
   * @option update Updates existing entity types and creates new if not present.
   */
  public function generateSiteStudioSettings($options = ['update' => FALSE, 'type' => NULL]) {
    $this->io()->success('Generating site studio settings.');
    $this->updateMode = $options['update'];
    $data = $this->getDataFromSheet(DstegConstants::SITE_STUDIO_SETTINGS);
    // Filtering the data further when command argument is provided.
    if (!empty($options['type'])) {
      foreach ($data as $key => $value) {
        if (str_replace(' ', '_', strtolower($value['type'])) !== $options['type']) {
          unset($data[$key]);
        }
      }
      sort($data);
    }

    // Site studio settings generated from DST sheet.
    $settings = $this->getSiteStudioData($data, $options['type']);
    foreach ($settings as $key => $value) {
      $type = $value['type'];
      $setting = $value['entity']['id'];
      $entity_type = 'cohesion_' . $type;
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($setting);
      if (!empty($entity)) {
        if ($this->updateMode && $data[$key][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($entity, $value['entity']);
          $this->io()->success($type . ' ' . $setting . " updated.");
          continue;
        }
        $this->io()->warning($type . ' ' . $setting . " Already exists. Skipping creation.");
        continue;
      }
      $status = $this->entityTypeManager->getStorage($entity_type)->create($value['entity'])->save();
      if ($status === SAVED_NEW) {
        $this->io()->success($type . ' ' . $setting . " is successfully created.");
      }
    }
  }

  /**
   * Get data needed for site studio entity.
   *
   * @param $data
   *   Array of Data.
   * @param $param
   *   Command argument.
   *
   * @return array|null
   *   Font stack data.
   */
  private function getSiteStudioData(array $data, $param) {
    $site_studio_data = [];
    foreach ($data as $item) {
      // To allow existing entity update.
      if (!$this->requiredFieldsCheck($item, 'Content type')) {
        continue;
      }
      if (!$this->validateMachineName($item['machine_name'])) {
        continue;
      }
      // Type value will distinguish between the cohesion entities.
      // Command with param -> type = param.
      // Command with no param -> type value from sheet data to be considered.
      $type = $param ?: str_replace(' ', '_', strtolower($item['type']));
      switch ($type) {
        // Prepare json values based on the item type; font stack, library & color.
        case 'font_stack':
          $json_values = ($item['type'] === 'Font Stack') ? $this->getFontStackJsonValues($item) : [];

          break;

        case 'font_library':
          $json_values = ($item['type'] === 'Font Library') ? $this->getFontLibraryJsonValues($item) : [];

          break;

        case 'color':
          $json_values = ($item['type'] === 'Color') ? $this->getColorJsonValues($item) : [];

          break;
      }
      // Site studio entity configuration.
      if (!empty($json_values)) {
        $entity = [
          'langcode' => 'en',
          'status' => TRUE,
          'label' => $item['label'],
          'id' => $item['machine_name'],
          'uuid' => $this->uuid->generate(),
          'json_values' => json_encode($json_values),
          'json_mapper' => '{}',
          'modified' => TRUE,
          'selectable' => TRUE,
          'locked' => FALSE,
          'weight' => 0,
        ];
        $site_studio_data[] = [
          'entity' => $entity,
          'type' => $type,
        ];
      }
    }

    return $site_studio_data;
  }

  /**
   * Prepares json value for cohesion font stack.
   *
   * @param $item
   *   The data coming from DST sheet.
   *
   * @return array
   *   Returns json_values.
   */
  protected function getFontStackJsonValues($item): array {
    return [
      'name' => $item['label'],
      'fontStack' => $item['metadata'],
      'variable' => '$coh-' . $item['machine_name'],
      'uid' => $item['machine_name'],
      'class' => '.coh-' . $item['machine_name'],
    ];
  }

  /**
   * Prepares json value for cohesion font library.
   *
   * @param $item
   *   The data coming from DST sheet.
   *
   * @return array
   *   Returns json_values.
   */
  protected function getFontLibraryJsonValues($item): array {
    return [
      'name' => $item['label'],
      'type' => 'import',
      'url' => $item['metadata'],
      'uid' => $item['machine_name'],
    ];
  }

  /**
   * Prepares json value for cohesion color.
   *
   * @param $item
   *   The data coming from DST sheet.
   *
   * @return array
   *   Returns json_values.
   */
  protected function getColorJsonValues($item): array {
    $rgba_color = $this->hex2rgba($item['metadata'], 1);
    if (array_key_exists('tag', $item) && $item['tag']) {
      $tag = [$item['tag']];
    }
    else {
      $tag = [];
    }
    return [
      'link' => TRUE,
      'value' => [
        'value' => [
          'hex' => $item['metadata'],
          'rgba' => $rgba_color,
        ],
      ],
      'uid' => $item['machine_name'],
      'name' => $item['label'],
      'class' => '.coh-color-' . $item['machine_name'],
      'variable' => '$coh-color-' . $item['machine_name'],
      'wysiwyg' => (array_key_exists('settings', $item) && $item['settings'] == 'ckeditor') ? TRUE : FALSE,
      'tags' => $tag,
    ];
  }

  /**
   * Converts hex code to rgba.
   *
   * @param $color
   *   Hex code coming from DST sheet.
   * @param $opacity
   *
   * @return string
   */
  public function hex2rgba($color, $opacity = FALSE): string {
    $default_color = 'rgb(0,0,0)';
    // Return default color if no color provided.
    if (empty($color)) {
      return $default_color;
    }

    // Ignore "#" if provided.
    if ($color[0] == '#') {
      $color = substr($color, 1);
    }

    // Check if color has 6 or 3 characters, get values.
    if (strlen($color) == 6) {
      $hex = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
    }
    elseif (strlen($color) == 3) {
      $hex = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
    }
    else {
      return $default_color;
    }

    // Convert hex values to rgb values.
    $rgb = array_map('hexdec', $hex);

    // Check if opacity is set(rgba or rgb)
    if ($opacity) {
      if (abs($opacity) > 1) {
        $opacity = 1.0;
      }
      $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    }
    else {
      $output = 'rgb(' . implode(",", $rgb) . ')';
    }

    // Return rgb(a) color string.
    return $output;
  }

}
