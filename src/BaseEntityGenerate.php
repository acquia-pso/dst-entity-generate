<?php

namespace Drupal\dst_entity_generate;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;

/**
 * Base class for all entity generate commnads.
 */
abstract class BaseEntityGenerate extends DrushCommands {

  use StringTranslationTrait;

  /**
   * GoogleSheetApi service class object.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Helper class for entity generation.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $helper;

  /**
   * BaseEntityGenerate constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(GoogleSheetApi $sheet, GeneralApi $generalApi) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->helper = $generalApi;
  }

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
