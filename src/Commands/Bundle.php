<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;

/**
 * Class provides functionality of Content types generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Bundle extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'content_type';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'content_types';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['node'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display mode repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   Display mode repository.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $displayRepository, GeneralApi $generalApi) {
    $this->entityTypeManager = $entityTypeManager;
    $this->displayRepository = $displayRepository;
    $this->helper = $generalApi;
  }

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:bundles
   * @aliases dst:bundles dst:b
   * @usage drush dst:generate:bundles
   */
  public function generateBundle() {
    $this->io()->success('Generating Drupal Content types.');
    // Call all the methods to generate the Drupal entities.
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $node_storage = $this->entityTypeManager->getStorage('node_type');
    $node_types = $this->getNodeTypeData($data);

    foreach ($node_types as $node_type) {
      $type = $node_type['type'];
      if (!\is_null($node_storage->load($type))) {
        $this->io()->warning("Node Type $type Already exists. Skipping creation...");
        continue;
      }
      $status = $node_storage->create($node_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Node Type $type is successfully created...");
      }

      // Create display modes for newly created content type.
      // Assign widget settings for the default form mode.
      $this->displayRepository->getFormDisplay('node', $type)->save();

      // Assign display settings for the display view modes.
      $this->displayRepository->getViewDisplay('node', $type)->save();
    }

    // Generate fields now.
    $bundle_type = 'Content type';
    $fields_data = $bundles_data = [];
    $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS);
    if (empty($fields_data)) {
      $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $bundle_type.");
      return self::EXIT_SUCCESS;
    }
    foreach ($data as $bundle) {
      if ($bundle['type'] === $bundle_type) {
        $bundles_data[$bundle['name']] = $bundle['machine_name'];
      }
    }
    $this->helper->generateEntityFields($bundle_type, $fields_data, $bundles_data);
  }

  /**
   * Get data needed for Node type entity.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   Node compliant data.
   */
  private function getNodeTypeData(array $data) {
    $node_types = [];
    foreach ($data as $item) {
      $node = [];
      $node['name'] = $item['name'];
      $node['type'] = $item['machine_name'];
      $node['description'] = $item['description'];
      \array_push($node_types, $node);
    }
    return $node_types;

  }

}
