<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * Sync configuration array.
   *
   * @var array|mixed|null
   */
  private $syncEntities;

  /**
   * Update identifier set in DST sheet.
   *
   * @var string
   */
  protected $updateFlag = 'c';

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository
   *   Display mode repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
                              ConfigFactoryInterface $configFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              EntityDisplayRepositoryInterface $displayRepository,
                              ModuleHandlerInterface $moduleHandler) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $this->updateFlag = $configFactory->get('dst_entity_generate.settings')->get('update_flag');
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
    if ($this->syncEntities && array_key_exists($entity, $this->syncEntities) && empty($this->syncEntities[$entity])) {
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
    $this->logger->debug(implode("<br />", $message));
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
    $field_configs = [
      'field_name' => $field['machine_name'],
      'entity_type' => $entity_type,
      'type' => $field['drupal_field_type'],
      'cardinality' => $this->getCardinality($field),
    ];
    if ($field['field_type'] === 'Layout Canvas (Site Studio)') {
      $field_configs['settings']['target_type'] = 'cohesion_layout';
    }
    elseif (array_key_exists('settings', $field) && !empty($field['settings'])) {
      $field_configs['settings'] = $field['settings'];
    }
    FieldStorageConfig::create($field_configs)->save();
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
      $field_configs = [
        'field_name' => $field_data['machine_name'],
        'description' => $field_data['help_text'],
        'entity_type' => $entity_type,
        'bundle' => $bundle_machine_name,
        'label' => $field_data['field_label'],
        'required' => $required,
      ];
      if (is_array($field_data['settings']) && array_key_exists('handler_settings', $field_data['settings'])) {
        $field_configs['settings'] = $field_data['settings']['handler_settings'];
      }
      if ($field_data['type'] === 'field_group' && $this->isModuleEnabled('field_group')) {
        $field_settings = [
          'children' => $field_data['children'],
          'format_type' => $field_data['format_type'],
          'label' => $field_data['field_label'],
          'region' => 'content',
          'weight' => 0,
          'parent_name' => '',
          'format_settings' => [
            'id' => '',
            'classes' => '',
            'description' => '',
            'required_fields' => $required,
          ],
        ];
        $this->displayRepository->getFormDisplay($entity_type, $bundle_machine_name)
          ->setThirdPartySetting('field_group', $field_data['machine_name'], $field_settings)
          ->save();

        $this->logger->notice($this->t('@field field group is created in bundle "@bundle"',
          [
            '@field' => $field_data['machine_name'],
            '@bundle' => $bundle->label(),
          ]
        ));
      }
      elseif ($field_data['type'] !== 'field_group') {
        $options = ['region' => 'content'];
        // Configuring form widget if it's not empty.
        if (!empty($field_data['drupal_field_form_widget'])) {
          $options['type'] = $field_data['drupal_field_form_widget'];
        }
        FieldConfig::create($field_configs)->save();
        // Set form display for new field.
        $this->displayRepository->getFormDisplay($entity_type, $bundle_machine_name)
          ->setComponent($field_data['machine_name'], $options)
          ->save();
        $this->logger->notice($this->t('@field field is created in bundle "@bundle"',
          [
            '@field' => $field_data['machine_name'],
            '@bundle' => $bundle->label(),
          ]
        ));
      }
    }
    else {
      $this->logger->warning($this->t('The @bundle bundle does not exists.', ['@bundle' => $bundle_machine_name]));
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
   * @param string $mode
   *   Command execution mode.
   *
   * @return mixed
   *   Returns array based on unmet dependency.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function fieldStorageHandler(array $field, string $entity_type, $mode = 'create') {
    if (empty($field)) {
      return FALSE;
    }
    $field_types = DstegConstants::FIELD_TYPES;
    if (!array_key_exists($field['field_type'], $field_types)) {
      $this->logger->warning($this->t(
        'Support for generating field of type @ftype is currently not supported.',
        ['@ftype' => $field['field_type']]
      ));
      return FALSE;
    }

    $field_meta = $field_types[$field['field_type']];
    if (array_key_exists('dependencies', $field_meta) && !empty($field_meta['dependencies'])) {
      $field = $this->fieldDependencyCheck($field_meta, $field);
      if (empty($field)) {
        return FALSE;
      }
    }

    $field_types = DstegConstants::FIELD_FORM_WIDGET;
    $field['drupal_field_form_widget'] = '';
    $is_empty_form_widget = FALSE;
    if (empty($field['form_widget']) || $field['form_widget'] === '-') {
      $is_empty_form_widget = TRUE;
    }
    if ($is_empty_form_widget === FALSE  && !array_key_exists($field['form_widget'], $field_types)) {
      $this->logger->warning($this->t(
        "Support for generating field of form widget '@fwidget' is currently not supported.",
        ['@fwidget' => $field['form_widget']]
      ));
      return FALSE;
    }
    elseif ($is_empty_form_widget === FALSE) {
      $field['drupal_field_form_widget'] = $field_types[$field['form_widget']];
    }

    if ($field && $field_meta['type'] !== 'field_group') {
      $field['drupal_field_type'] = $field_meta['type'];
      $field_storage = FieldStorageConfig::loadByName($entity_type, $field['machine_name']);
      if (empty($field_storage)) {
        $this->createFieldStorage($field, $entity_type);
        $this->logger->notice($this->t('Field storage created for @field',
          ['@field' => $field['machine_name']]
        ));
      }
      else {
        $storage_changed = FALSE;
        $message = '';
        if ($field_storage->getType() !== $field['drupal_field_type']) {
          $storage_changed = TRUE;
          $message = $this->t(
            'Field storage "@field_machine_name" already exist with type "@field_type". Change machine name of "@field_label" in "@bundle" to create new field or select same field type as existing to reuse it. Skipping for now.',
            [
              '@field_machine_name' => $field['machine_name'],
              '@field_type' => array_keys(array_combine(array_keys(DstegConstants::FIELD_TYPES), array_column(DstegConstants::FIELD_TYPES, 'type')), $field_storage->getType())[0],
              '@bundle' => $field['bundle'],
              '@field_label' => $field['field_label'],
            ]
          );
        }
        switch ($mode) {
          case 'create':
            if ($storage_changed) {
              $this->logger->warning($message);
              return FALSE;
            }
            break;

          case 'update':
            if ($this->updateFlag === $field['x']) {
              // Update field storage configurations.
              if ($storage_changed) {
                $this->logger->warning($message);
                return FALSE;
              }
              else {
                $this->updateFieldStorageConfigurations($field_storage, $field);
                $this->logger->notice($this->t('Field storage config updated for @field',
                  ['@field' => $field['machine_name']]
                ));
              }
            }
            else {
              $this->logger->warning($this->t(
                'The field @field is not configured to update in DST sheet.',
                ['@field' => $field['machine_name']]
              ));
            }
            break;

          case 'delete':
            // @todo Delete mode placeholder.
            break;
        }
      }
    }
    elseif ($field_meta['type'] === 'field_group') {
      $field['format_type'] = $field_meta['format_type'];
      $field['type'] = $field_meta['type'];
    }
    return $field;
  }

  /**
   * Update Field storage configurations.
   *
   * @param \Drupal\field\Entity\FieldStorageConfig $field_storage_config
   *   Field storage config object.
   * @param array $data
   *   Field array from DST sheet.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFieldStorageConfigurations(FieldStorageConfig $field_storage_config, array $data) {
    $allowed_configs = [
      'cardinality',
    ];
    foreach ($allowed_configs as $allowed_config) {
      switch ($allowed_config) {
        case 'cardinality':
          $field_storage_config->setCardinality($this->getCardinality($data));
          break;
      }
    }
    $field_storage_config->save();
  }

  /**
   * Get cardinality value from field array.
   *
   * @param array $data
   *   Field array from DST sheet.
   *
   * @return int|mixed
   *   Returns cardinality value.
   */
  public function getCardinality(array $data) {
    $cardinality = $data['vals.'];
    if ($cardinality === '*') {
      $cardinality = -1;
    }
    elseif ($cardinality === '-') {
      $cardinality = 1;
    }
    return $cardinality;
  }

  /**
   * Helper function to generate fields.
   *
   * @param string $bundle_type
   *   Bundle type, ie. Content type.
   * @param array $fields_data
   *   The field data to be created.
   * @param array $bundles_data
   *   The bundle data to create fields.
   * @param string $mode
   *   Command execution mode. Either Create or Update or Delete.
   *
   * @return mixed
   *   Can return the command output or FALSE.
   */
  public function generateEntityFields(string $bundle_type, array $fields_data, array $bundles_data, string $mode = 'create') {

    $result = TRUE;
    $this->logger->notice($this->t('Generating all Drupal Entities Fields.'));

    $fields_data = $this->sortFieldData($fields_data);
    foreach ($fields_data as $field) {
      $bundleVal = '';
      $bundle = $field['bundle'];
      $field_machine_name = $field['machine_name'];
      if (!$this->validateMachineName($field_machine_name)) {
        $message = $this->t("The machine-readable name must contain only lowercase letters, numbers, and underscores with maximum length of 32. Skipping bundle creation with machine name @machine_name",
          ['@machine_name' => $field_machine_name]);
        $this->logger->warning($message);
        continue;
      }
      $bundle_name = trim(substr($bundle, 0, strpos($bundle, "(")));
      if (array_key_exists($bundle_name, $bundles_data)) {
        $bundleVal = $bundles_data[$bundle_name];
      }
      else {
        // Skip fields if entity type is not ready to implement.
        continue;
      }
      if (isset($bundleVal)) {
        try {
          $entity_type_id = DstegConstants::ENTITY_TYPE_MAPPING[$bundle_type]['entity_type_id'];
          $entity_type = DstegConstants::ENTITY_TYPE_MAPPING[$bundle_type]['entity_type'];
          $drupal_field = FieldConfig::loadByName($entity_type, $bundleVal, $field_machine_name);

          // Skip if field is present.
          if (!empty($drupal_field)) {
            switch ($mode) {
              case 'create':
                $this->logger->warning($this->t(
                  'The field @field is present in @ctype. Skipping.',
                  [
                    '@field' => $field['machine_name'],
                    '@ctype' => $bundleVal,
                  ]
                ));
                break;

              case 'update':
                if ($this->updateFlag === $field['x']) {
                  // Update field configurations.
                  $this->updateFieldConfigurations($drupal_field, $field);
                  $this->logger->notice($this->t(
                    'The field configurations for @field are updated in @ctype.',
                    [
                      '@field' => $field['machine_name'],
                      '@ctype' => $bundleVal,
                    ]
                  ));
                  // Update field storage configurations.
                  $this->fieldStorageHandler($field, $entity_type, 'update');
                }
                else {
                  $this->logger->warning($this->t(
                    'The field @field is not configured to update in DST sheet.',
                    ['@field' => $field['machine_name']]
                  ));
                }
                break;

              case 'delete':
                // @todo Placeholder for delete mode.
                break;
            }
            continue;
          }

          // Create field storage.
          $field = $this->fieldStorageHandler($field, $entity_type);
          if ($field) {
            if ($field['type'] === 'field_group') {
              $field['children'] = $this->getFieldGroupChildren($field, $fields_data);
            }
            $this->addField($bundleVal, $field, $entity_type_id, $entity_type);
          }
        }
        catch (\Exception $exception) {
          $message = $this->t('Exception occurred while generating @field_machine_name in @entity: @exception', [
            '@exception' => $exception->getMessage(),
            '@entity' => DstegConstants::FIELDS,
            '@field_machine_name' => $field_machine_name,
          ]);
          $this->logger->error($message);
          $result = FALSE;
        }
      }
      else {
        $this->logger->notice($this->t(
          'The field @field in @ctype is mis-configured. Skipping.',
          [
            '@field' => $field['machine_name'],
            '@ctype' => $bundleVal,
          ]
        ));
      }
    }
    return $result;
  }

  /**
   * Update field configurations.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *   Field config object.
   * @param array $data
   *   Data array from DST sheet.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFieldConfigurations(FieldConfig $field_config, array $data) {
    $allowed_configs = [
      'label',
      'description',
      'required',
    ];
    foreach ($allowed_configs as $allowed_config) {
      switch ($allowed_config) {
        case 'label':
          $field_config->setLabel($data['field_label']);
          break;

        case 'description':
          $field_config->setDescription($data['help_text']);
          break;

        case 'required':
          $required = $data['req'] === 'y';
          $field_config->setRequired($required);
          break;
      }
    }
    $field_config->save();
  }

  /**
   * Helper function to check field level dependency.
   *
   * @param array $field_meta
   *   Field meta data.
   * @param array $field
   *   Field array.
   *
   * @return mixed
   *   Returns array or false based on dependency checks.
   */
  public function fieldDependencyCheck(array $field_meta, array $field) {
    $dependencies = $field_meta['dependencies'];
    foreach ($dependencies as $dependency_type => $dependency) {
      foreach ($dependency as $dependency_key => $dependency_value) {
        switch ($dependency_key) {
          case 'module':
            if (!$this->isModuleEnabled($dependency_value)) {
              $this->logger->warning($this->t(
                'The @module module is not installed. Skipping @field field generation.',
                [
                  '@module' => $dependency_value,
                  '@field' => $field['machine_name'],
                ]
              ));
              return FALSE;
            }
            break;

          case 'settings':
            foreach ($dependency_value as $setting_key => $setting_value) {
              switch ($setting_value) {
                case 'allowed_values':
                  $settings = array_map('trim', explode(',', $field['settings/notes']));
                  $field['settings']['allowed_values'] = array_combine($settings, $settings);
                  break;

                case 'target_type':
                  $temp = explode(' (', rtrim($field['ref._bundle'], ')'));
                  $target_bundle = $temp[0];
                  $target_bundle_type = $temp[1];
                  $entity_type_mapping = DstegConstants::ENTITY_TYPE_MAPPING;
                  if (!array_key_exists($target_bundle_type, $entity_type_mapping)) {
                    $this->logger->warning($this->t(
                      'The @target_bundle_type is not supported entity type. Skipping @field field generation.',
                      [
                        '@target_bundle_type' => $target_bundle_type,
                        '@field' => $field['machine_name'],
                      ]
                    ));
                    return FALSE;
                  }
                  $entity_type_storage = $this->entityTypeManager->getStorage($entity_type_mapping[$target_bundle_type]['entity_type_id']);
                  if (empty($entity_type_storage)) {
                    $this->logger->warning($this->t(
                      'The @target_bundle_type is invalid or not exist. Skipping @field field generation.',
                      [
                        '@target_bundle_type' => $target_bundle_type,
                        '@field' => $field['machine_name'],
                      ]
                    ));
                    return FALSE;
                  }
                  // @todo Machine name should be read from bundles tab.
                  $target_bundle_machine_name = strtolower(str_replace(" ", "_", $target_bundle));
                  $entity_storage = $entity_type_storage->load($target_bundle_machine_name);
                  if (empty($entity_storage)) {
                    $this->logger->warning($this->t(
                      'The @target_bundle does not exist. Skipping @field field generation.',
                      [
                        '@target_bundle' => $target_bundle,
                        '@field' => $field['machine_name'],
                      ]
                    ));
                    return FALSE;
                  }
                  $field['settings']['target_type'] = $entity_type_mapping[$target_bundle_type]['entity_type'];
                  $field['settings']['handler_settings'] = [
                    'handler' => 'default',
                    'handler_settings' => [
                      'target_bundles' => [
                        $entity_storage->id(),
                      ],
                    ],
                  ];
                  break;
              }
            }
            break;
        }
      }
    }
    return $field;
  }

  /**
   * Helper function to validate machine name.
   *
   * @param string $machine_name
   *   Machine name string to validate.
   * @param int $length
   *   Expected maximum length.
   *
   * @return bool
   *   Returns true or false based on matching result.
   */
  public function validateMachineName(string $machine_name, int $length = 32) {
    $result = FALSE;
    $pattern = "/^[a-z0-9_]+$/";
    if (strlen($machine_name) <= $length && preg_match($pattern, $machine_name)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Sorts field data.
   *
   * Fields which have field group, should be created at the end.
   *
   * @param array $fields
   *   All applicable fields from sheet.
   *
   * @return array
   *   Returns sorted fields data.
   */
  public function sortFieldData(array $fields) {
    $field_group = [];
    foreach ($fields as $key => $field) {
      $field_group[$key] = $field['field_group'];
    }
    array_multisort($field_group, SORT_ASC, $fields);
    return $fields;
  }

  /**
   * Fetch all child fields for field group field.
   *
   * @param array $field_group_field
   *   Array of field group field.
   * @param array $fields_data
   *   Array of all applicable fields from sheet.
   *
   * @return array
   *   Returns child fields for field group field.
   */
  public function getFieldGroupChildren(array $field_group_field, array $fields_data) {
    $children = [];
    foreach ($fields_data as $field) {
      if ($field['bundle'] === $field_group_field['bundle'] && $field_group_field['field_label'] === $field['field_group']) {
        $children[] = $field['machine_name'];
      }
    }
    return $children;
  }

}
