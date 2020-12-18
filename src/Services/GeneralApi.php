<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class GoogleSheetApi to connect with Google Sheets.
 */
class GeneralApi {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * Entity type manager service definition.
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
   * Module handler interface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
                              KeyValueFactoryInterface $key_value,
                              ConfigFactoryInterface $configFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityDisplayRepositoryInterface $displayRepository,
                              ModuleHandlerInterface $moduleHandler) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $this->entityGenerateStorage = $key_value->get('dst_entity_generate_storage');
    $this->debugMode = $this->entityGenerateStorage->get('debug_mode');
    $this->entityTypeManager = $entityTypeManager;
    $this->displayRepository = $displayRepository;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * The function to decide whether the sync of the Entity supplied is possible.
   *
   * @param string $entity
   *   The entity type to check.
   *
   * @return bool
   *   Returns TRUE if the entity should be skipped.
   */
  public function skipEntitySync(string $entity) {
    $skipEntitySync = FALSE;
    $entity = strtolower(str_replace(" ", "_", $entity));
    if (!empty($this->syncEntities) && $this->syncEntities[$entity]['All'] !== 'All') {
      $skipEntitySync = TRUE;
    }
    return $skipEntitySync;
  }

  /**
   * If "Debug mode" is on, log a message using the logger.
   *
   * @param array $message
   *   Message which needs to be logged.
   */
  public function logMessage(array $message) {
    if ($this->debugMode) {
      $this->logger->debug(implode("<br />", $message));
    }
  }

  /**
   * Helper function to get all the existing entities.
   *
   * @param string $entity_type
   *   The entity type for which we require to load the entities for.
   * @param string $loading_type
   *   The method supports loading just entity types or multiple.
   *
   * @return mixed
   *   Return the multiple entities which got loaded.
   */
  public function getAllEntities(string $entity_type, string $loading_type = 'default') {
    if ($loading_type === 'all') {
      $results = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
    }
    else {
      $results = $this->entityTypeManager->getStorage($entity_type);
    }
    return $results;
  }

  /**
   * Create field storage helper function.
   *
   * @param array $field
   *   Field details.
   * @param string $entity_type
   *   Entity type.
   */
  public function createFieldStorage(array $field, string $entity_type) {
    $cardinality = $field['vals.'];
    if ($cardinality === '*') {
      $cardinality = -1;
    }
    elseif ($cardinality === '-') {
      $cardinality = 1;
    }
    FieldStorageConfig::create([
      'field_name' => $field['machine_name'],
      'entity_type' => $entity_type,
      'type' => $field['drupal_field_type'],
      'cardinality' => $cardinality,
    ])->save();
  }

  /**
   * Helper function to add field to content type.
   *
   * @param string $bundle_machine_name
   *   Content type machine name.
   * @param array $field_data
   *   Field data.
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $entity_type
   *   Entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addField(string $bundle_machine_name, array $field_data, string $entity_type_id, string $entity_type) {
    $entity_types_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $bundle = $entity_types_storage->load($bundle_machine_name);
    if ($bundle != NULL) {

      $required = $field_data['req'] === 'y';
      // Create field instance.
      FieldConfig::create([
        'field_name' => $field_data['machine_name'],
        'entity_type' => $entity_type,
        'bundle' => $bundle_machine_name,
        'label' => $field_data['field_label'],
        'required' => $required,
      ])->save();

      // Set form display for new field.
      $this->displayRepository->getFormDisplay($entity_type, $bundle_machine_name)
        ->setComponent($field_data['machine_name'],
          ['region' => 'content']
        )
        ->save();

      $this->logger->notice($this->t('@field field is created in bundle "@bundle"',
        [
          '@field' => $field_data['machine_name'],
          '@bundle' => $bundle->label(),
        ]
      ));
    }
    else {
      $this->logger->notice($this->t('The @bundle bundle does not exists.', ['@bundle' => $bundle_machine_name]));
    }
  }

  /**
   * Helper function to check if module is enabled.
   *
   * @param string $module_name
   *   The module name to check.
   *
   * @return bool
   *   Returns module exists status.
   */
  public function isModuleEnabled(string $module_name) {
    return $this->moduleHandler->moduleExists($module_name);
  }

  /**
   * Helper function to handle field storage.
   *
   * @param array $field
   *   Field data from Google sheet.
   * @param string $entity_type
   *   Entity type.
   *
   * @return boolean
   *   Returns boolean based on unmet dependency.
   */
  public function fieldStorageHandler(array $field, string $entity_type) {
    if (empty($field)) {
      return FALSE;
    }
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field['machine_name']);
    if (!empty($field_storage)) {
      return FALSE;
    }
    $field_types = DstegConstants::FIELD_TYPES;
    if (!array_key_exists($field['field_type'], $field_types)) {
      $this->logger->notice($this->t(
        'Support for generating field of type @ftype is currently not supported.',
        ['@ftype' => $field['field_type']]
      ));
      return FALSE;
    }
    $field_meta = $field_types[$field['field_type']];
    if (array_key_exists('module_dependency', $field_meta) && !empty($field_meta['module_dependency']) && !$this->isModuleEnabled($field_meta['module_dependency'])) {
      $this->logger->notice($this->t(
        'The @module module is not installed. Skipping @field field generation.',
        [
          '@module' => $field_meta['module_dependency'],
          '@field' => $field['machine_name'],
        ]
      ));
      return FALSE;
    }
    $field['drupal_field_type'] = $field_meta['type'];
    $this->createFieldStorage($field, $entity_type);
    $this->logger->notice($this->t('Field storage created for @field',
      ['@field' => $field['machine_name']]
    ));
    return TRUE;
  }

}
