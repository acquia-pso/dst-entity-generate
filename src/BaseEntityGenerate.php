<?php

namespace Drupal\dst_entity_generate;

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
  protected $entity;

  /**
   * Validate hook for commands.
   *
   * @hook validate
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
   * @throws \Exception
   */
  public function validateEntityForImport() {
    $enabled_entities = \Drupal::configFactory()->get('dst_entity_generate.settings')->get('sync_entities');
    if ($enabled_entities[$this->entity] !== $this->entity) {
      throw new \Exception("Entity $this->entity is not enabled for import. Aborting..");
    }
  }

  /**
   * Get data from drupal spec tool google sheet.
   *
   * @param string $sheet
   *   Sheet tab name.
   *
   * @return array
   *   Data.
   */
  protected function getDataFromSheet(string $sheet) {
    $cache_key = 'dst_sheet_data.' . \strtolower($sheet);
    $cache_api = \Drupal::cache();

    if (!empty($cache_api->get($cache_key))) {
      $data = $cache_api->get($cache_key);
    }
    else {
      $google_sheet_api = \Drupal::service('dst_entity_generate.google_sheet_api');
      $data = $google_sheet_api->getData($sheet);
      // Store cached data for 6 hours.
      $cache_api->set($cache_key, $data, microtime(TRUE) + 21600);
    }
    return $this->filterEntityTypeSpecificData($data);
  }

  private function filterEntityTypeSpecificData($data) {
    if ($this->entity === '') {
      return $data;
    }

    foreach ($data as $item) {
      if ()
    }
  }

}
