<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class GoogleSheetApi.
 */
class GoogleSheetApi {

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * KeyValue store having google sheet settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $googleSheetStorage;

  /**
   * KeyValue store having entity generate settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $entityGenerateStorage;

  /**
   * Messenger service definition.
   *
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * Google Service Sheets definition.
   *
   * @var \Google_Service_Sheets
   */
  protected $googleSheetService;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
                              KeyValueFactoryInterface $key_value,
                              MessengerInterface $messenger) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->googleSheetStorage = $key_value->get('dst_google_sheet_storage');
    $this->entityGenerateStorage = $key_value->get('dst_entity_generate_storage');
    $this->debugMode = $this->entityGenerateStorage->get('debug_mode');
    $this->messenger = $messenger;

    if (empty($this->googleSheetStorage->get('name'))
      || empty($this->googleSheetStorage->get('credentials'))
      || empty($this->googleSheetStorage->get('access_token'))
      || empty($this->googleSheetStorage->get('spreadsheet_id'))) {
      // Log the missing configuration of google spreadsheet.
      $this->logger->error("Data missing in configuration of google spreadsheet.");
    } else {
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
    $google_client = [];
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
      $this->logger->info('Google Client created successfully.');
      if ($this->debugMode) {
        $this->messenger->addStatus('Google Client created successfully.');
      }
    } catch (\Exception $e) {
      // Log the access error of google spreadsheet.
      $this->logger->error('Error creating Google Client @error',[
        '@error' => $e->getMessage()
      ]);
      if ($this->debugMode) {
        $this->messenger->addError('Error creating Google Client @error',[
          '@error' => $e->getMessage()
        ]);
      }
    }
    return $google_client;
  }

  /**
   * @param string $range
   *   The location to get data from [Sheet Name]![TopLeftCell]:[BottomRightCell]

   * @return array
   *   An multi-level array of retrieved values keyed by row and then column.
   *   0 indexed and the rows/columns start at 0 based on the *range* not the
   *   whole sheet.
   */
  public function getData($range) {
    $sheet_values = [];
    try {
      if (!empty($this->googleSheetService)) {
        $google_sheet_service = $this->googleSheetService;
        $response = $google_sheet_service->spreadsheets_values->get($this->googleSheetStorage->get('spreadsheet_id'), $range);
        if (!empty($response)) {
          $this->logger->info('Data fetched successfully.');
          if ($this->debugMode) {
            $this->messenger->addStatus('Data fetched successfully.');
          }
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
        } else {
          $this->logger->notice('Response is empty.');
          if ($this->debugMode) {
            $this->messenger->addError('Response is empty.');
          }
        }
      }
    }
    catch (\Exception $exception) {
      $this->logger->notice('Error in fetching data from Spec Tool Sheet @exception', [
        '@exception' => $exception
      ]);
      if ($this->debugMode) {
        $this->messenger->addError('Error in fetching data from Spec Tool Sheet @exception',[
          '@exception' => $exception->getMessage()
        ]);
      }
    }
    return $sheet_values;
  }

}
