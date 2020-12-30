<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\field\Entity\FieldConfig;

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
  protected $dstEntityMame = 'content_types';

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
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $displayRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->displayRepository = $displayRepository;
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

    // Here comes field generation code.
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

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $result = TRUE;

    $this->logger->notice($this->t('Generating Drupal Fields.'));
    // Call all the methods to generate the Drupal entities.
    $fields_data = $this->sheet->getData(DstegConstants::FIELDS);
    if (empty($fields_data)) {
      return $result;
    }

    $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);
    foreach ($bundles_data as $bundle) {
      if (strtolower($bundle['type']) === strtolower('Content type')) {
        $bundleArr[$bundle['name']] = $bundle['machine_name'];
      }
    }

    foreach ($fields_data as $field) {
      if ($field['x'] !== 'w') {
        continue;
      }
      $bundleVal = '';
      $bundle = $field['bundle'];
      $bundle_name = substr($bundle, 0, -15);
      if (array_key_exists($bundle_name, $bundleArr)) {
        $bundleVal = $bundleArr[$bundle_name];
      }

      // Skip fields which are not part of content type.
      if (!str_contains($field['bundle'], 'Content type')) {
        continue;
      }

      if (isset($bundleVal)) {
        try {
          $entity_type_id = 'node_type';
          $entity_type = 'node';
          $drupal_field = FieldConfig::loadByName($entity_type, $bundleVal, $field['machine_name']);

          // Skip if field is present.
          if (!empty($drupal_field)) {
            $this->logger->notice($this->t(
              'The field @field is present in @ctype. Skipping.',
              [
                '@field' => $field['machine_name'],
                '@ctype' => $bundleVal,
              ]
            ));
            continue;
          }

          // Create field storage.
          $result = $this->helper->fieldStorageHandler($field, $entity_type);
          if ($result) {
            $this->helper->addField($bundleVal, $field, $entity_type_id, $entity_type);
          }
        }
        catch (\Exception $exception) {
          $this->displayAndLogException($exception, DstegConstants::FIELDS);
          $result = FALSE;
        }
      }
    }
    return $result;
  }

}
