<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\Component\Uuid\UuidInterface;

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
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::SITE_STUDIO_SETTINGS);
    // Site studio settings generated from DST sheet.
    $settings = $this->getSiteStudioData($data, $options['type']);
    foreach ($settings as $key => $value) {
      $type = $value['type'];
      $setting = $value['entity']['id'];
      $entity_type = ($type === 'Font') ? 'cohesion_font_stack' : 'cohesion_color';
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($setting);
      if (!empty($entity)) {
        if ($this->updateMode && $data[$key][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($entity, $value['entity']);
          $this->io()->success($type . ' ' . $setting .  " updated.");
          continue;
        }
        $this->io()->warning($type . ' ' . $setting . " Already exists. Skipping creation.");
        continue;
      }
      $status = $this->entityTypeManager->getStorage($entity_type)->create($value['entity'])->save();
      if ($status === SAVED_NEW) {
        $this->io()->success($type . ' ' . $setting .  " is successfully created.");
      }
    }
  }

  /**
   * Get data needed for site studio entity.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Font stack data.
   */
  private function getSiteStudioData(array $data, $param) {
    $site_studio_data = [];
    foreach ($data as $item) {
      // To allow existing entity update.
      if (!$this->validateMachineName($item['machine_name'])) {
        continue;
      }
      // Type value will distinguish between the cohesion entities.
      // Command with param -> type = param.
      // Command with no param -> type value from sheet data to be considered.
      $type = $param ?: strtolower($item['type']);
      switch ($type) {
        case 'font':
          // Prepare json values only if item has font data.
          $json_values = ($item['type'] === 'Font') ? $this->getFontJsonValues($item) : [];

          break;
        case 'color':
          // Prepare json values only if item has color data.
          $json_values = ($item['type'] === 'Color') ? $this->getColorJsonValues($item) : [];

          break;
      }
      // Site studio entity configuration.
      if (!empty($json_values)) {
        $entity = [
          'langcode' => 'en',
          'status' => true,
          'label' => $item['label'],
          'id' => $item['machine_name'],
          'uuid' => $this->uuid->generate(),
          'json_values' => json_encode($json_values),
          'json_mapper' => '{}',
          'modified' => true,
          'selectable' => true,
          'locked' => false,
          'weight' => 0,
        ];
        $site_studio_data[] = [
          'entity' => $entity,
          'type' => $item['type'],
        ];
      }
    }

    return $site_studio_data;
  }

  /**
   * Prepares json value for cohesion font stack.
   *
   * @param $item
   *  The data coming from DST sheet.
   * @return array
   *  Returns json_values.
   */
  protected function getFontJsonValues($item): array {
    return [
      'name' => $item['label'],
      'fontStack' => $item['metadata'],
      'variable' => '$coh-' . $item['machine_name'],
      'uid' => $item['machine_name'],
      'class' => '.coh-' . $item['machine_name'],
    ];

  }

  /**
   * Prepares json value for cohesion color.
   *
   * @param $item
   *  The data coming from DST sheet.
   * @return array
   *  Returns json_values.
   */
  protected function getColorJsonValues($item): array {
    $tags = [];
    $rgba_color = $this->hex2rgba($item['metadata'], 1);
    if (array_key_exists('tag', $item) && $item['tag']) {
      $tag = [$item['tag']];
    } else {
      $tag = [];
    }
    return [
      'link' => true,
      'value' => [
        'value' => [
          'hex' => $item['metadata'],
          'rgba' => $rgba_color,
        ],
      ],
      'uid' => $item['machine_name'],
      'name' => $item['label'],
      'class' => '.coh-color-' . $item['machine_name'],
    ];
  }

  /**
   * Converts hex code to rgba.
   *
   * @param $hex
   *  Hex code coming from DST sheet.
   * @param $alpha
   * @return string
   */
  protected function hex2rgba($hex, $alpha): string {
    return 'RGBA Value';
  }
}
