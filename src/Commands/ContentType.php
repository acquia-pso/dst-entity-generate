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
class ContentType extends BaseEntityGenerate {

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
   * Content type generator constructor.
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
   * @command dst:generate:content_types
   * @aliases dst:content_types dst:ct
   * @usage drush dst:generate:content_types
   * @options update Update existing entities.
   */
  public function generateContentType($options = ['update' => FALSE]) {
    $this->io()->success('Generating Drupal Content types.');
    // Call all the methods to generate the Drupal entities.
    $this->updateMode = $options['update'];
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $node_storage = $this->entityTypeManager->getStorage('node_type');
    $node_types = $this->getNodeTypeData($data);

    foreach ($node_types as $index => $node_type) {
      $type = $node_type['type'];
      $entity = 'node';
      $url_alias_pattern = $node_type['url_alias_pattern'];
      $entity_type = $node_storage->load($type);
      if (!\is_null($entity_type)) {
        $this->generatePathautoPattern($type, $url_alias_pattern, $entity);
        if ($this->updateMode && $data[$index][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($entity_type, $node_type);
          $this->io()->success("Node Type $type updated.");
          continue;
        }
        $this->io()->warning("Node Type $type Already exists. Skipping creation...");
        continue;
      }
      $status = $node_storage->create($node_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Node Type $type is successfully created...");
        $this->generatePathautoPattern($type, $url_alias_pattern, $entity);
      }

      // Create display modes for newly created content type.
      // Assign widget settings for the default form mode.
      $this->displayRepository->getFormDisplay('node', $type)->save();

      // Assign display settings for the display view modes.
      $this->displayRepository->getViewDisplay('node', $type)->save();
    }

    // Generate fields now.
    $bundle_type = 'Content type';
    $bundles_data = [];
    $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS, FALSE);
    $fields_data = $this->filterEntityTypeSpecificData($fields_data, 'bundle');
    if (empty($fields_data)) {
      $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $bundle_type.");
      return self::EXIT_SUCCESS;
    }
    foreach ($node_types as $bundle) {
      $bundles_data[$bundle['name']] = $bundle['type'];
    }
    $this->helper->generateEntityFields($bundle_type, $fields_data, $bundles_data, $mode);

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
      $node['url_alias_pattern'] = $item['url_alias_pattern'];
      \array_push($node_types, $node);
    }
    return $node_types;

  }

}
