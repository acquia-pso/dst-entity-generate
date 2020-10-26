<?php

namespace Drupal\dst_entity_generate;

use Drush\Commands\DrushCommands;

/**
 * Base class for all entity generate commnads.
 */
abstract class BaseEntityGenerate extends DrushCommands {

  /**
   * Validate hook for commands.
   *
   * @hook validate
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

}
