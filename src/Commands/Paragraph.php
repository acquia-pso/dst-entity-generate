<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\Services\GeneralApi;

/**
 * Class provides functionality of Content types generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Paragraph extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'paragraph_type';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'paragraph_types';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['paragraphs'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct the Paragrpah class object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GeneralApi $generalApi) {
    $this->entityTypeManager = $entityTypeManager;
    $this->helper = $generalApi;
  }

  /**
   * Generate all the Drupal entities from DEG sheet.
   *
   * @command deg:generate:paragraphs
   * @aliases deg:para deg:p
   * @options update Update existing entities.
   */
  public function generateParagraph($options = ['update' => FALSE]) {
    $this->io()->success('Generating Drupal Paragraphs...');
    $this->updateMode = $options['update'];
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraphs_type');
    $paragraph_types = $this->getParagraphTypeData($data);
    foreach ($paragraph_types as $index => $paragraph_type) {
      $id = $paragraph_type['id'];
      $paragraph_type_entity = $paragraph_storage->load($id);
      if (!\is_null($paragraph_type_entity)) {
        if ($this->updateMode && $data[$index][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($paragraph_type_entity, $paragraph_type);
          $this->io()->success("Paragraph Type $id updated.");
          continue;
        }
        $this->io()->warning("Paragraph Type $id Already exists. Skipping creation...");
        continue;
      }
      $status = $paragraph_storage->create($paragraph_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Paragraph Type $id is successfully created...");
      }
    }

    // Generate fields now.
    $bundle_type = 'Paragraph type';
    $bundles_data = [];
    $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS, FALSE);
    $fields_data = $this->filterEntityTypeSpecificData($fields_data, 'bundle');

    if (empty($fields_data)) {
      $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $bundle_type.");
      return self::EXIT_SUCCESS;
    }
    foreach ($paragraph_types as $paragraph_type) {
      $bundles_data[$paragraph_type['label']] = $paragraph_type['id'];
    }

    $this->helper->generateEntityFields($bundle_type, $fields_data, $bundles_data, $mode);
  }

  /**
   * Get data needed for paragraph type entity.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Paragraph compliant data.
   */
  private function getParagraphTypeData(array $data) {
    $paragraph_types = [];
    foreach ($data as $item) {
      $paragraph = [];
      $paragraph['label'] = $item['name'];
      $paragraph['id'] = $item['machine_name'];
      $paragraph['description'] = $item['description'];
      \array_push($paragraph_types, $paragraph);
    }
    return $paragraph_types;
  }

}
