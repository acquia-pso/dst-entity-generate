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

    $requiredConfigs = [
      'name',
      'credentials',
      'access_token',
      'spreadsheet_id',
    ];

    foreach ($requiredConfigs as $config) {
      if (empty($googleSheetStorage->get($config))) {
        throw new \Exception("Please configure $config in google sheet credentials configurations.");
      }
    }
  }

  /**
   * Helper function to say message on cli as well log them.
   *
   * @param string $message
   *   The translated message string.
   * @param string $type
   *   The type of message to display.
   */
  protected function showMessage(string $message, string $type = 'progress') {

    switch ($type) {
      case 'info':
        $this->yell($message, 100, 'blue');
        break;

      case 'warning':
        $this->yell($message, 100, 'yellow');
        break;

      case 'error':
        $this->yell($message, 100, 'red');
        break;

      default:
        $this->say($message);
    }
  }

}
