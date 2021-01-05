<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\Component\Plugin\PluginManagerInterface;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   Display mode repository.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $general_api
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $display_repository, GeneralApi $general_api) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $display_repository;
    $this->helper = $general_api;
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
      /** @var \Drupal\media\MediaTypeInterface $media_type_obj */
      $media_type_obj = reset($media_storage->loadByProperties(['id'=>$type]));
      // If the media source is using a source field, ensure it's
      // properly created.
      $source = $media_type_obj->getSource();
      $source_field = $source->getSourceFieldDefinition($media_type_obj);
      if (!$source_field) {
        $source_field = $source->createSourceField($media_type_obj);
        /** @var \Drupal\field\FieldStorageConfigInterface $storage */
        $storage = $source_field->getFieldStorageDefinition();
        if ($storage->isNew()) {
          $storage->save();
        }
        $source_field->save();

        // Add the new field to the default form and view displays for this
        // media type.
        if ($source_field->isDisplayConfigurable('form')) {
          $display = $this->displayRepository->getFormDisplay('media', $media_type_obj->id());
          $source->prepareFormDisplay($media_type_obj, $display);
          $display->save();
        }
        if ($source_field->isDisplayConfigurable('view')) {
          $display = $this->displayRepository->getViewDisplay('media', $media_type_obj->id());

          // Remove all default components.
          foreach (array_keys($display->getComponents()) as $name) {
            $display->removeComponent($name);
          }
          $source->prepareViewDisplay($media_type_obj, $display);
          $display->save();
        }
      }
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
      $media['source_configuration']['source_field'] = 'field_media_image';
      $media['description'] = $item['description'];
      \array_push($media_types, $media);
    }
    return $media_types;

  }

}
