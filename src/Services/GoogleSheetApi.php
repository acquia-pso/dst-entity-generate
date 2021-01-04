<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GoogleSheetApi to connect with Google Sheets.
 */
class GoogleSheetApi {

  use StringTranslationTrait;

  /**
   * KeyValue store having google sheet settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $googleSheetStorage;

  /**
   * KeyValue store having entity generate settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $entityGenerateStorage;

  /**
   * Google Service Sheets definition.
   *
   * @var \Google_Service_Sheets
   */
  protected $googleSheetService;

  /**
   * DSTEG General service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $generalApi;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(KeyValueFactoryInterface $keyvalue,
                              GeneralApi $generalApi) {
    $this->googleSheetStorage = $keyvalue->get('dst_google_sheet_storage');
    $this->entityGenerateStorage = $keyvalue->get('dst_entity_generate_storage');
    $this->generalApi = $generalApi;

    if (empty($this->googleSheetStorage->get('name'))
      || empty($this->googleSheetStorage->get('credentials'))
      || empty($this->googleSheetStorage->get('access_token'))
      || empty($this->googleSheetStorage->get('spreadsheet_id'))) {
      // Log the missing configuration of google spreadsheet.
      $this->generalApi->logMessage(
        [$this->t('Data missing in configuration of google spreadsheet.')]
      );
    }
    else {
      $client = $this->getClient();
      if (!empty($client)) {
        $this->googleSheetService = new \Google_Service_Sheets($client);
      }
    }
  }

  /**
   * Create google client to get data from google sheets.
   *
   * @return array|\Google_Client
   *   Google Client if successfully created.
   */
  public function getClient() {
    $google_client = $logMessages = [];
    try {
      $google_client = new \Google_Client();
      $google_client->setApplicationName($this->googleSheetStorage->get('name'));
      $google_client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
      $google_client->setAuthConfig(json_decode($this->googleSheetStorage->get('credentials'), TRUE));
      $google_client->setAccessType('offline');

      // Load previously authorized credentials from a file.
      $access_token = json_decode($this->googleSheetStorage->get('access_token'), TRUE);
      $google_client->setAccessToken($access_token);

      // Refresh the token if it's expired.
      if ($google_client->isAccessTokenExpired()) {
        $google_client->fetchAccessTokenWithRefreshToken($google_client->getRefreshToken());
        $settings = $this->googleSheetStorage;
        if (!empty($settings)) {
          $settings->set('access_token', json_encode($google_client->getAccessToken()));
        }
      }
      $logMessages[] = $this->t('Google Client created successfully.');
    }
    catch (\Exception $e) {
      // Log the access error of google spreadsheet.
      $logMessages[] = $this->t('Error creating Google Client @error', ['@error' => $e->getMessage()]);
    }
    $this->generalApi->logMessage($logMessages);
    return $google_client;
  }

  /**
   * General method to fetch the Google Sheet data.
   *
   * @param string $range
   *   The location to get data from [Sheet Name]![TopLeftCell]
   *   [BottomRightCell].
   *
   * @return array
   *   An multi-level array of retrieved values keyed by row and then column.
   *   0 indexed and the rows/columns start at 0 based on the *range* not the
   *   whole sheet.
   */
  public function getData(string $range) {
    $sheet_values = $logMessages = [];
    try {
      if (!empty($this->googleSheetService)) {
        $google_sheet_service = $this->googleSheetService;
        $response = $google_sheet_service->spreadsheets_values->get($this->googleSheetStorage->get('spreadsheet_id'), $range);
        if (!empty($response)) {
          $logMessages[] = $this->t('Data fetched successfully.');
          $sheet_values = $response->getValues();
          $headers = $sheet_values[0];

          // Get header rows. Assuming first row is header row.
          array_splice($sheet_values, 0, 1);

          // Replacing indexes with header values as keys.
          foreach ($sheet_values as $key => $value) {
            $new_sheet_value = [];
            foreach ($headers as $header_key => $header_value) {
              $lower_header_value = preg_replace('/\s+/', '_', strtolower($header_value));
              if (isset($lower_header_value) && isset($value[$header_key])) {
                $new_sheet_value[$lower_header_value] = $value[$header_key];
              }
            }
            $sheet_values[$key] = $new_sheet_value;
          }
        }
        else {
          $logMessages[] = $this->t('Response is empty.');
        }
      }
    }
    catch (\Exception $exception) {
      $logMessages[] = $this->t('Error while fetching data from DST Sheet @exception', [
        '@exception' => $exception,
      ]);
    }
    $this->generalApi->logMessage($logMessages);
    return $sheet_values;
  }

}
