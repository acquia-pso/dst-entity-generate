<?php

namespace Drupal\dst_entity_generate\Commands;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\dst_entity_generate\Services\GeneralApi;

/**
 * Class provides drush command to access token of authorization.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class getAccessToken extends BaseEntityGenerate {
  /**
   * Google Sheet Api service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * DSTEG General service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $generalApi;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   General Api service definition.
   *   LoggerChannelFactory service definition.
   */
  public function __construct(GoogleSheetApi $sheet, GeneralApi $generalApi) {
    parent::__construct($sheet, $generalApi);
  }

  /**
   * Get access token.
   *
   * @command dst:get:access_token
   * @aliases dst:get_access_token
   * @usage drush dst:get:access_token
   */
  public function getAccessToken()
  {
    $application_name = $this->ask($this->t("Please enter application name."));
    $credentials_json = $this->ask($this->t("Please enter the json data of credentials."));

    if (!empty($credentials_json) && !empty($application_name)) {
      try {
        $client = new \Google_Client();
        $client->setApplicationName(trim($application_name));
        $client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(json_decode(trim($credentials_json), true));
        $client->setAccessType('offline');
        $browser_link = $client->createAuthUrl();
        // Request authorization from the user.
        $this->yell("Authorization to access Google Spreadsheet needed. Open the following link in your browser and get the verification code:");
        $this->yell($browser_link);
        $verification = $this->ask("Enter verification code: ");
        // Exchange authorization code for an access token.
        $access_token = $client->fetchAccessTokenWithAuthCode($verification);
        $this->yell("Access token: ");
        $this->yell(json_encode($access_token));
      }
      catch (\Exception $exception) {
        $exception_message = $this->t('Exception occurred @exception', [
          '@exception' => $exception,
        ]);
        $this->yell($exception_message);
      }
    } else {
      $this->say($this->t('The application name and credentials should be entered.'));
    }
  }
}
