<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
/**
 * Class GoogleSheetApi.
 */
class GoogleSheetApi {
  /**
   * Google client object.
   *
   * @var \Google_Client
   */
  protected $client;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;
  /**
   * Drupal\Core\KeyValueStore\KeyValueFactoryInterface definition.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, KeyValueFactoryInterface $key_value) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->keyValue = $key_value->get("dst_google_sheet_storage");

    if (empty($this->keyValue->get('name')) || empty($this->keyValue->get('credentials')) || empty($this->keyValue->get('access_token')) || empty($this->keyValue->get('spreadsheet'))) {
      // Log the missing configuration of google spreadsheet.
      $this->logger->error("Data missing in configuration of google spreadsheet.");
    } else {
      $this->client = $this->getClient();
    }
  }

  /**
   * Returns an authorized API client.
   */
   public function getClient() {
     try {
       $google_client = new \Google_Client();
       $google_client->setApplicationName($this->keyValue->get('name'));
       $google_client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
       $google_client->setAuthConfig(json_decode($this->keyValue->get('credentials'), TRUE));
       $google_client->setAccessType('offline');

       // Load previously authorized credentials from a file.
       $access_token = json_decode($this->keyValue->get('access_token'), TRUE);
       $google_client->setAccessToken($access_token);

       // Refresh the token if it's expired.
       if ($google_client->isAccessTokenExpired()) {
         $google_client->fetchAccessTokenWithRefreshToken($google_client->getRefreshToken());
         $settings = $this->keyValue;
         if (!empty($settings)) {
           $settings->set('access_token', json_encode($google_client->getAccessToken()));
         }
       }
       $this->logger->info('Google Client created successfully.');
       return $google_client;
     } catch (\Exception $e) {
       // Log the access error of google spreadsheet.
       $this->logger->error('Error creating Google Client @error',[
         '@error' => $e->getMessage()
       ]);
     }

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
      $google_sheets_service = new \Google_Service_Sheets($this->client);
      $response = $google_sheets_service->spreadsheets_values->get($this->keyValue->get('spreadsheet'), $range);
      if (!empty($response)) {
        $this->logger->info('Data fetched successfully.');
        $sheet_values = $response->getValues();
        $headers = $sheet_values[0];

        // Get header rows. Assuming first row is header row.
        array_splice($sheet_values, 0, 1);

        // Replacing indexes with header values as keys.
        foreach ($sheet_values as $key => $value) {
          $new_sheet_value = [];
          foreach ($headers as $header_key => $header_value) {
            $new_sheet_value[
            preg_replace('/\s+/', '_', strtolower($header_value))
            ] = $value[$header_key];
          }
          $sheet_values[$key] = $new_sheet_value;
        }
      }
      else {
        $this->logger->notice('Response is empty');
      }
    }
    catch (\Exception $exception) {
      $this->logger->notice('Error in fetching data from Spec Tool Sheet @exception', [
        '@exception' => $exception
      ]);
    }
    return $sheet_values;
  }

}
