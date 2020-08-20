<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
/**
 * Class GoogleSpreadsheetAccess.
 */
class GoogleSpreadsheetAccess {
  /**
   * Google client object.
   *
   * @var \Google_Client
   */
  protected $client;
  /**
   * Application name string.
   *
   * @var string
   */
  protected $name;
  /**
   * Google credentials json.
   *
   * @var string
   */
  protected $credentials;
  /**
   * Google access token json.
   *
   * @var string
   */
  protected $accessToken;
  /**
   * Google spreadsheet unique id.
   *
   * @var string
   */
  protected $spreadsheet;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;
  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {

    $this->logger = $logger_factory;
    $this->config = $config_factory;
    $this->name = $this->config->get('google_spreadsheet.settings')->get('name');
    $this->credentials = $this->config->get('google_spreadsheet.settings')->get('credentials');
    $this->accessToken = $this->config->get('google_spreadsheet.settings')->get('access_token');
    $this->spreadsheet = $this->config->get('google_spreadsheet.settings')->get('spreadsheet');

    if (empty($this->name) || empty($this->credentials) || empty($this->accessToken) || empty($this->spreadsheet)) {
      // Log the missing configuration of google spreadsheet.
      $this->logger->get('dst_entity_generate')->error("Data missing in configuration of google spreadsheet.");
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
       $client->setApplicationName($this->name);
       $client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
       $client->setAuthConfig(json_decode($this->credentials, TRUE));
       $client->setAccessType('offline');

       // Load previously authorized credentials from a file.
       $access_token = json_decode($this->accessToken, TRUE);
       $client->setAccessToken($access_token);

       // Refresh the token if it's expired.
       if ($client->isAccessTokenExpired()) {
         $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
         $settings = $this->config->getEditable('google_spreadsheet.settings');
         if (!empty($settings)) {
           $settings->set('access_token', json_encode($client->getAccessToken()));
         }
       }
       return $client;
     } catch (\Exception $e) {
       // Log the access error of google spreadsheet.
       $this->logger->get('dst_entity_generate')->error($e->getMessage());
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
    $response = $service->spreadsheets_values->get($this->spreadsheet, $range);
    $values = $response->getValues();
    return $values;
  }

}
