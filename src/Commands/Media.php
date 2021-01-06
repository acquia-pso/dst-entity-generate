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
   * Media source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $sourceManager;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   Display mode repository.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $source_manager
   *   Media source plugin manager.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $general_api
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $display_repository, PluginManagerInterface $source_manager, GeneralApi $general_api) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $display_repository;
    $this->sourceManager = $source_manager;
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
      $media_type_obj = reset($media_storage->loadByProperties(['id' => $type]));
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

      // Generate fields now.
      $fields_data = $bundles_data = [];
      $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS);
      if (empty($fields_data)) {
        $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $this->entity.");
        return self::EXIT_SUCCESS;
      }
      foreach ($data as $bundle) {
        if ($bundle['type'] === $this->entity) {
          $bundles_data[$bundle['name']] = $bundle['machine_name'];
        }
      }
      $this->helper->generateEntityFields($this->entity, $fields_data, $bundles_data);
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
    $source_options = $this->getAvailableMediaSourceOptions();
    foreach ($data as $item) {
      $media_name = $item['name'];
      if (!isset($item['settings/notes']) || empty($item['settings/notes'])) {
        $this->io()->error("Cannot create \"$media_name\" media without proper source. Please define source in \"settings/notes\" column of the sheet.");
        continue;
      }

      $source_option = $item['settings/notes'];
      if (!\in_array($source_option, $source_options)) {
        $implode_source_options = \implode(',', $source_options);
        $this->io()->error("Source - \"$source_option\" is not a valid source. Please use one of the source from in \"$implode_source_options\" options. Skipping creation of \"$media_name\"...");
        continue;
      }
      $media = [];
      $media['label'] = $media_name;
      $media['id'] = $item['machine_name'];
      $media['source'] = \array_search($source_option, $source_options);
      $media['source_configuration']['source_field'] = 'field_media_image';
      $media['description'] = $item['description'];
      \array_push($media_types, $media);
    }
    return $media_types;

  }

  /**
   * Get available media source options.
   *
   * @return array
   *   Array of media source options.
   */
  private function getAvailableMediaSourceOptions() {
    $plugins = $this->sourceManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }

}
