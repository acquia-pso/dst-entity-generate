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
class Media extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'media_type';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'media_types';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['media'];

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
   * @command dst:generate:media
   * @aliases dst:media
   * @usage drush dst:generate:media
   */
  public function generateBundle() {
    $this->io()->success('Generating Drupal Media types.');
    // Call all the methods to generate the Drupal entities.
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $media_storage = $this->entityTypeManager->getStorage('media_type');
    $media_types = $this->getMediaTypeData($data);

    foreach ($media_types as $media_type) {
      $type = $media_type['id'];
      if (!\is_null($media_storage->load($type))) {
        $this->io()->warning("media Type $type Already exists. Skipping creation...");
        continue;
      }
      $status = $media_storage->create($media_type)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("media Type $type is successfully created...");
      }

      // Create display modes for newly created content type.
      // Assign widget settings for the default form mode.
      $this->displayRepository->getFormDisplay('media', $type)->save();

      // Assign display settings for the display view modes.
      $this->displayRepository->getViewDisplay('media', $type)->save();
    }
  }

  /**
   * Get data needed for media type entity.
   *
   * @param array $data
   *   Array of Data.
   *
   * @return array|null
   *   media compliant data.
   */
  private function getMediaTypeData(array $data) {
    $media_types = [];
    foreach ($data as $item) {
      $media = [];
      $media['label'] = $item['name'];
      $media['id'] = $item['machine_name'];
      $media['source'] = 'image';
      $media['description'] = $item['description'];
      \array_push($media_types, $media);
    }
    return $media_types;

  }

}
