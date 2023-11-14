<?php

namespace Drupal\dst_entity_generate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Base class for all entity generate commands.
 */
abstract class BaseEntityGenerate extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Machine name of entity which is going to import.
   *
   * @var string
   */
  protected $entity = '';

  /**
   * Name of the entity from DST overview sheet.
   *
   * @var string
   */
  protected $dstEntityName = '';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = [];

  /**
   * Command with Update mode.
   *
   * @var bool
   */
  protected $updateMode = FALSE;

  /**
   * Update identifier set in DST sheet.
   *
   * @var string
   */
  protected $updateFlag = 'c';

  /**
   * Implementation status column name from DST sheet.
   *
   * @var string
   */
  protected $implementationFlagColumn = 'x';

  /**
   * List of required fields to create entity.
   *
   * @var array
   */
  protected $requiredFields = [];

  /**
   * Validate hook for commands.
   *
   * @hook validate
   *
   * @throws \Exception
   */
  public function validateGoogleSheetCreds() {
    $keyValueStorage = \Drupal::service('keyvalue');

    $googleSheetStorage = $keyValueStorage->get('dst_google_sheet_storage');

    $requiredConfigs = ['name', 'credentials', 'access_token', 'spreadsheet_id'];

    foreach ($requiredConfigs as $config) {
      if (empty($googleSheetStorage->get($config))) {
        throw new \Exception("Please configure $config in google sheet credentials configurations.");
      }
    }
  }

  /**
   * Helper function to display and log exception.
   *
   * @param \Exception $exception
   *   Exception object.
   * @param string $entity
   *   Entity name on which exception occurred.
   */
  public function displayAndLogException(\Exception $exception, string $entity) {
    $message = $this->t('Exception occurred while generating @entity: @exception', [
      '@exception' => $exception->getMessage(),
      '@entity' => $entity,
    ]);
    $this->yell($message);
    $this->logger->error($message);
  }

  /**
   * Validates if given entity is enabled for import or not.
   *
   * @hook pre-validate
   *
   * @throws \Exception
   */
  public function validateEntityForImport() {
    $enabled_entities = \Drupal::configFactory()->get('dst_entity_generate.settings')->get('sync_entities');
    if ($enabled_entities[$this->dstEntityName] !== $this->dstEntityName && $this->dstEntityName !== 'all') {
      $choice = $this->io()->choice("Entity $this->dstEntityName is not enabled for import. Do you want to enable it?",
        ['Yes', 'No'],
        'Yes'
      );
      switch ($choice) {
        case 0:
          $this->enableEntitySync($this->dstEntityName);
          break;

        case 1:
          throw new \Exception("Entity $this->dstEntityName is not enabled for import. Aborting...");
      }
    }
  }

  /**
   * Helper function to enable entity sync.
   *
   * @param string $entity_name
   *   Entity name to enable sync.
   */
  public function enableEntitySync(string $entity_name) {
    $dst_entity_generate_settings = \Drupal::configFactory()->getEditable('dst_entity_generate.settings');
    $sync_entities = $dst_entity_generate_settings->get('sync_entities');
    $sync_entities[$entity_name] = $entity_name;
    $dst_entity_generate_settings->set('sync_entities', $sync_entities)->save();
  }

  /**
   * Validates if given modules are enabled or not.
   *
   * @hook validate
   *
   * @throws \Exception
   */
  public function validateModulesStatus() {
    if (empty($this->dependentModules)) {
      return;
    }

    $moduleHandler = \Drupal::moduleHandler();
    $disabledModules = [];
    foreach ($this->dependentModules as $module) {
      if (!$moduleHandler->moduleExists($module)) {
        \array_push($disabledModules, $module);
      }
    }

    if (!empty($disabledModules)) {
      $message = $this->formatPlural(
        count($disabledModules),
        '%module module is disabled.',
        '%module modules are disabled.',
        ['%module' => implode(', ', $disabledModules)]
      );
      $this->io()->warning(strip_tags($message));
      $choice = $this->io()->choice("Do you want to enable it? Note: After enabling modules, re-run same command to process entities.",
        ['Yes', 'No'],
        'Yes'
      );
      switch ($choice) {
        case 0:
          $this->enableModules($disabledModules);
          $this->io()->note("Terminating command. Please re-run same command to process entities.");
          return FALSE;

        case 1:
          $this->io()->note("Terminating command.");
          return FALSE;
      }
    }
  }

  /**
   * Enable list of modules.
   *
   * @param array $modules
   *   List of modules to enable.
   */
  public function enableModules(array $modules) {
    $modulesString = \implode(',', $modules);
    $this->io()->text("Installing module(s) $modulesString");
    \Drupal::service('module_installer')->install($modules, TRUE);
  }

  /**
   * Get data from drupal spec tool google sheet.
   *
   * @param string $sheet
   *   Sheet tab name.
   * @param bool $filter
   *   Boolean indicating whether data needs to be further filtered or not.
   *
   * @return array
   *   Data.
   */
  protected function getDataFromSheet(string $sheet, $filter = TRUE) {
    $cache_key = 'dst_sheet_data.' . \strtolower($sheet);
    $cache_api = \Drupal::cache();

    if (!empty($cache_api->get($cache_key))) {
      $data = $cache_api->get($cache_key)->data;
    }
    else {
      $google_sheet_api = \Drupal::service('dst_entity_generate.google_sheet_api');
      $data = $google_sheet_api->getData($sheet);
      // Store cached data for 6 hours.
      $cache_api->set($cache_key, $data, microtime(TRUE) + 21600);
    }

    return ($filter) ? $this->filterEntityTypeSpecificData($data) : $this->filterApprovedData($data);
  }

  /**
   * Get entity specific data from retrieved google sheet data.
   *
   * @param array $data
   *   Retrieved data.
   * @param string $key
   *   Key of the DST sheet to filter the data.
   *
   * @return array|null
   *   Filtered data or empty.
   */
  protected function filterEntityTypeSpecificData(array $data, string $key = 'type') {
    if ($this->entity === '') {
      return $this->filterApprovedData($data);
    }

    $filtered_data = [];
    foreach ($data as $item) {
      // @todo Have to refactor below commented code to work for all the tabs other then the `bundles`.
      // if (!isset($item['type'])) {
      // throw new \Exception("Type column is require to identify
      // the type of entity.
      // Please make sure you are using correct Drupal Spec Tool sheet.
      // Aborting...");
      // }
      if (strpos($this->converToMachineName($item[$key]), $this->entity) !== FALSE) {
        \array_push($filtered_data, $item);
      }
    }

    return $this->filterApprovedData($filtered_data);
  }

  /**
   * Filter entity type data based on row status.
   *
   * @param array $data
   *   Data fetched from google sheet.
   *
   * @return array|null
   *   Approved data.
   */
  private function filterApprovedData(array $data) {
    if (empty($data)) {
      return;
    }

    $config = \Drupal::config('dst_entity_generate.settings');
    $this->implementationFlagColumn = $config->get('column_name');
    $column_value = $config->get('column_value');
    $this->updateFlag = $config->get('update_flag');

    $approved_data = [];

    foreach ($data as $item) {
      if (!isset($item[$this->implementationFlagColumn])) {
        $this->io()->warning($this->t("Please provide correct value for @implementation_flag_column column. Aborting...", ['@implementation_flag_column' => $this->implementationFlagColumn]));
        continue;
      }
      if ($item[$this->implementationFlagColumn] === $column_value) {
        \array_push($approved_data, $item);
      }
      if ($this->updateMode) {
        if ($item[$this->implementationFlagColumn] === $this->updateFlag) {
          \array_push($approved_data, $item);
        }
      }
    }
    return $approved_data;
  }

  /**
   * Convert a string to machine name.
   *
   * @param string $name
   *   Human readable name to covert into machine name.
   *
   * @return string
   *   Machine readable name.
   */
  private function converToMachineName($name) {
    return strtolower(str_replace(" ", "_", $name));
  }

  /**
   * Helper function to generate pathauto pattern.
   */
  public function generatePathautoPattern($bundle, $alias, $entity) {
    $patternStatus = FALSE;
    $moduleHandler = \Drupal::moduleHandler();
    if (!$moduleHandler->moduleExists('pathauto')) {
      $this->io()->warning($this->t('Please install pathauto module.'));
      return FALSE;
    }
    if (isset($alias)) {
      $patternStatus = TRUE;
      $pattern_id = $bundle . '_pattern';
      $pattern = $this->entityTypeManager->getStorage('pathauto_pattern')->load($pattern_id);
      if ($pattern) {
        $this->io()->warning($this->t('Alias for @bundle is already present, skipping.', ['@bundle' => $bundle]));
        return FALSE;
      }

      if ($patternStatus) {
        $pattern = $this->entityTypeManager->getStorage('pathauto_pattern')->create([
          'id' => $pattern_id,
          'label' => $bundle . ' pattern',
          'type' => 'canonical_entities:' . $entity,
          'pattern' => $alias,
          'weight' => -5,
        ]);

        // Add the bundle condition.
        $pattern->addSelectionCondition([
          'id' => 'entity_bundle:' . $entity,
          'bundles' => [$bundle => $bundle],
          'negate' => FALSE,
        ]);

        $pattern->save();
        $this->io()->success($this->t('Alias for @bundle is created.', ['@bundle' => $bundle]));
      }
    }
    else {
      $this->io()->warning($this->t('Alias for @bundle is not available, skipping.', ['@bundle' => $bundle]));
    }
  }

  /**
   * Function to update entity type configurations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_type
   *   Entity type object.
   * @param array $data
   *   DST sheet data to update entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEntityType(EntityInterface $entity_type, array $data) {
    $allowed_fields = DstegConstants::ENTITY_TYPE_UPDATE_ALLOWED_FIELDS[$entity_type->getEntityTypeId()];
    foreach ($data as $field_name => $field_value) {
      if (in_array($field_name, $allowed_fields)) {
        $entity_type->set($field_name, $field_value);
      }
    }
    $entity_type->save();
  }

  /**
   * Function to check data having required fields to create entity.
   *
   * @param array $data
   *   Entity data from sheet.
   * @param string $type
   *   Entity type.
   *
   * @return bool
   *   Return status based on fields availability.
   */
  public function requiredFieldsCheck(array $data, string $type = 'Entity type') {
    $missing_fields = [];
    foreach ($this->requiredFields as $requiredField) {
      if (array_key_exists($requiredField, $data) && $data[$requiredField] == '') {
        $missing_fields[] = $requiredField;
      }
    }
    if (!empty($missing_fields)) {
      $this->io()->warning("$type can not be created with empty " . implode(', ', $missing_fields) . ". Skipping creation...");
      $status = FALSE;
    }
    else {
      $status = TRUE;
    }
    return $status;
  }

  /**
   * Helper function to validate machine name.
   *
   * @param string $machine_name
   *   Machine name string to validate.
   * @param int $length
   *   Expected maximum length.
   * @param string $separator
   *   Separator to match in string.
   *
   * @return bool
   *   Returns true or false based on matching result.
   */
  public function validateMachineName(string $machine_name, int $length = 32, string $separator = '_') {
    $result = FALSE;
    $pattern = "/^[a-z0-9$separator]+$/";
    if (strlen($machine_name) <= $length && preg_match($pattern, $machine_name)) {
      $result = TRUE;
    }
    else {
      $message = "The machine-readable name must contain only lowercase letters, numbers, and underscores with maximum length of $length. Skipping bundle creation with machine name $machine_name";
      $this->io()->warning($message);
    }
    return $result;
  }

}
