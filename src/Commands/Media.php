<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\OptionalDependencyHandler;
use Drupal\media\MediaSourceManager;

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
   * @param \Drupal\dst_entity_generate\Services\OptionalDependencyHandler $optional_dependency_handler
   *   Optional Dependency handler.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $general_api
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $display_repository, OptionalDependencyHandler $optional_dependency_handler, GeneralApi $general_api) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $display_repository;
    $media_source_manager = $optional_dependency_handler->getMediaSourceManager();
    if ($media_source_manager instanceof MediaSourceManager) {
      $this->sourceManager = $media_source_manager;
    }
    $this->helper = $general_api;
  }

  /**
   * Generate all the Drupal media type from DEG sheet.
   *
   * @command deg:generate:media
   * @aliases deg:media
   * @usage drush deg:media
   *   Generates Media types with fields if not present.
   * @option update Update existing entity types with fields and creates new if not present.
   */
  public function generateBundle($options = ['update' => FALSE]) {
    $this->io()->success('Generating Drupal Media types.');
    // Call all the methods to generate the Drupal entities.
    $this->updateMode = $options['update'];
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $media_storage = $this->entityTypeManager->getStorage('media_type');
    $media_types = $this->getMediaTypeData($data);

    foreach ($media_types as $index => $media_type) {
      $type = $media_type['id'];
      $media_type_entity = $media_storage->load($type);
      if (!\is_null($media_type_entity)) {
        if ($this->updateMode && $data[$index][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($media_type_entity, $media_type);
          $this->io()->success("Media Type $type updated.");
          continue;
        }
        $this->io()->warning("Media Type $type Already exists. Skipping creation...");
        continue;
      }

      $status = $media_storage->create($media_type)->save();

      if ($status === SAVED_NEW) {
        $this->io()->success("Media Type $type is successfully created...");
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
    }

    // Generate fields now.
    $bundle_type = 'Media type';
    $fields_data = $bundles_data = [];
    $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS, FALSE);
    $fields_data = $this->filterEntityTypeSpecificData($fields_data, 'bundle');
    if (empty($fields_data)) {
      $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $bundle_type.");
      return self::EXIT_SUCCESS;
    }
    foreach ($media_types as $media_type) {
      $bundles_data[$media_type['label']] = $media_type['id'];
    }
    $this->helper->generateEntityFields($bundle_type, $fields_data, $bundles_data, $mode);

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
