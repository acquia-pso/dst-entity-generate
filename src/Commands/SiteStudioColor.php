<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class provides functionality of Site Studio Integration for Color generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class SiteStudioColor extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'site_studio_color';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['cohesion'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * List of required fields to create entity.
   *
   * @var array
   */
  protected $requiredFields = ['id', 'machine_name'];

  /**
   * DstegMenu constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate all the Site Studio Colors from DEG sheet.
   *
   * @command deg:generate:sscolor
   * @aliases deg:ssc
   * @usage drush deg:ssc
   *   Generates site studio colors if not present.
   * @usage drush deg:ssc --update
   *   Update the existing colors.
   * @option update Update existing entity types and creates new if not present.
   */
  public function generateColor($options = ['update' => FALSE]) {
    $this->io()->success('Generating Site Studio Color.');
    $this->updateMode = $options['update'];
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::SITE_STUDIO_COLOR);
    $cohesion_color_storage = \Drupal::entityTypeManager()->getStorage('cohesion_color');
    $cohesion_color_types = $this->getColorTypeData($data);
    foreach ($cohesion_color_types as $index => $cohesion_color_type) {
      $type = $cohesion_color_type['id'];
      $cohesion_color_type_entity = $cohesion_color_storage->load($type);
      if (!\is_null($cohesion_color_type_entity)) {
        if ($this->updateMode && $data[$index][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($cohesion_color_type_entity, $cohesion_color_type);
          $this->io()->success("Color $type updated.");
          continue;
        }
        $this->io()->warning("Color $type Already exists. Skipping creation...");
        continue;
      }
      $status = $cohesion_color_storage->create($cohesion_color_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Color $type is successfully created...");
      }
    }
  }


  /**
   * Get data needed for Site Studio Color.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Color compliant data.
   */
  private function getColorTypeData(array $data) {
    $color_types = [];
    foreach ($data as $item) {
      if (!$this->requiredFieldsCheck($item, 'Site Studio Color')) {
        continue;
      }
      if (!$this->validateMachineName($item['machine_name'])) {
        continue;
      }
      $color = [];
      $uuid = \Drupal::service('uuid')->generate();
      $hex = $item['hex'];
      $rgba = $item['rgba'];
      $class = $item['class'];
      $variable = $item['variable'];
      $id = $item['id'];
      $name = $item['name'];
      $json_values = "{\"link\":true,\"value\":{\"value\":{\"hex\":\"$hex\",\"rgba\":\"rgba($rgba)\"}},\"uid\":\"$id\",\"name\":\"$name\",\"class\":\"$class\",\"variable\":\"$variable\",\"inuse\":\"FALSE\"}";

      $color['uuid'] = $uuid;
      $color['langcode'] = 'en';
      $color['status'] = 'true';
      $color['label'] = $item['machine_name'];
      $color['id'] = $item['id'];
      $color['json_values'] = $json_values;
      $color['json_mapper'] = '{}';
      $color['last_entity_update'] = '';
      $color['locked'] = false;
      $color['modified'] = true;
      $color['selectable'] = true;
      $color['weight'] = 0;

      \array_push($color_types, $color);
    }
    return $color_types;
  }
}
