<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
/**
 * Class GoogleSpreadsheetAccess.
 */
class GoogleSheetAccess {
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
   * Drupal\Core\Config\ConfigFactoryInterface definition.
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
       $client = new \Google_Client();
       $client->setApplicationName($this->keyValue->get('name'));
       $client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
       $client->setAuthConfig(json_decode($this->keyValue->get('credentials'), TRUE));
       $client->setAccessType('offline');

       // Load previously authorized credentials from a file.
       $access_token = json_decode($this->keyValue->get('access_token'), TRUE);
       $client->setAccessToken($access_token);

       // Refresh the token if it's expired.
       if ($client->isAccessTokenExpired()) {
         $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
         $settings = $this->keyValue;
         if (!empty($settings)) {
           $settings->set('access_token', json_encode($client->getAccessToken()));
         }
       }
       $this->logger->info('Google Client ceated successfully.');
       return $client;
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
    $service = new \Google_Service_Sheets($this->client);
    $response = $service->spreadsheets_values->get($this->keyValue->get('spreadsheet'), $range);
    $this->logger->info('Data fetched successfully.');
    return $response->getValues();
  }

}
