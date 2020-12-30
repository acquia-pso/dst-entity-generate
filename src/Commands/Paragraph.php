<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
  protected $dstEntityMame = 'paragraph_types';

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
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:paragraphs
   * @aliases dst:para dst:p
   */
  public function generateParagraph() {
    $this->io()->success('Generating Drupal Paragraphs...');
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraphs_type');
    $paragraph_types = $this->getParagraphTypeData($data);
    foreach ($paragraph_types as $paragraph_type) {
      $id = $paragraph_type['id'];
      if (!\is_null($paragraph_storage->load($id))) {
        $this->io()->warning("Paragraph Type $id Already exists. Skipping creation...");
        continue;
      }
      $status = $paragraph_storage->create($paragraph_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Paragraph Type $id is successfully created...");
      }
    }
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
