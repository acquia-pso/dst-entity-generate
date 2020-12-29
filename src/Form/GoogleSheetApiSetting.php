<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Google Api Settings Form.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class GoogleSheetApiSetting extends FormBase {

  use StringTranslationTrait;

  /**
   * Variable for steps.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * Variable for verification link.
   *
   * @var string
   */
  protected $verificationLink = '';

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a DstGoogleSheetSetting object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key value store.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The messenger definition.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $keyValueFactory, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $keyValueFactory;
    $this->messenger = $messenger;
    $this->logger = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('messenger'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dst_google_sheet_setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $store = $this->keyValue->get("dst_google_sheet_storage");

    if ($this->step == 1) {
      $form['step'] = [
        '#type' => 'item',
        '#markup' => "<b>Step - 1</b>",
      ];

      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Application Name'),
        '#required' => TRUE,
        '#description' => $this->t('Give Application Name like "Google Sheets API Application".'),
        '#default_value' => (isset($store) && !empty($store->get('name'))) ? $store->get('name') : '',
      ];

      $form['credentials_json_file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload credentials.json File'),
        '#required' => TRUE,
        '#description' => '<p>' . $this->t('To get credentials.json file, go to') . ' <a href="https://developers.google.com/sheets/api/quickstart/php" rel="nofollow noindex noopener external ugc" target="_blank">' . $this->t('Google API Credentials') . '</a> ' . $this->t('and just complete step 1.') . '</p>',
        '#upload_validators' => ['file_validate_extensions' => ['json']],
        '#upload_location' => 'private://',
      ];

      $form['spreadsheet_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Google Sheet Id'),
        '#required' => TRUE,
        '#description' => $this->t(
          'Copy the spreadsheet unique id from google sheet url and paste in this field. It looks like 1xJOEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30.'
        ),
        '#default_value' => (isset($store) && !empty($store->get('spreadsheet_id'))) ? $store->get('spreadsheet_id') : '',
      ];

      if (isset($store) && !empty($store->get('credentials'))) {
        $form['credentials'] = [
          '#type' => 'item',
          '#title' => $this->t('Uploaded Credentials JSON'),
          '#markup' => (isset($store) && !empty($store->get('credentials'))) ? $store->get('credentials') : '',
        ];
      }
    }

    if ($this->step == 2) {

      $form['step'] = [
        '#type' => 'item',
        '#markup' => "<b>Step - 2</b>",
      ];

      if (!empty($this->verificationLink)) {
        $form['verification_link'] = [
          '#type' => 'item',
          '#markup' => $this->t("Authorization to access Google Spreadsheet needed. Open the following link in your browser and get the verification code:") . "<br>" . $this->verificationLink,
        ];
      }

      $form['verification_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Enter verification code'),
        '#required' => TRUE,
      ];

      $button_label = $this->t('Submit');
    }
    else {
      $button_label = $this->t('Next');
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $button_label,
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $store = $this->keyValue->get("dst_google_sheet_storage");
    if ($this->step < 2) {
      if ($form_state->getValue('credentials_json_file') == NULL) {
        $form_state->setErrorByName('credentials_json_file', $this->t('Invalid File.'));
      }
      else {
        $credentials_json_file = $form_state->getValue('credentials_json_file');
        $file = $this->entityTypeManager->getStorage('file')->load($credentials_json_file[0]);
        $credential_data = trim(file_get_contents($file->getFileUri()));
        if (empty($credential_data)) {
          $form_state->setErrorByName('credentials_json_file', $this->t('Empty File.'));
        }
        else {
          $store->set('credentials', $credential_data);
          $store->set('name', $form_state->getValue('name'));
          $store->set('spreadsheet_id', $form_state->getValue('spreadsheet_id'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $store = $this->keyValue->get("dst_google_sheet_storage");
    if ($this->step < 2) {
      $credentials_json = $store->get('credentials');
      $name = $store->get('name');
      if (!empty($credentials_json) && !empty($name)) {
        $msg = $this->getAccessToken($credentials_json, $name, 1);

        if ($msg['message_type'] == 'error') {
          $this->messenger->addError($msg['message']);
        }
        else {
          $this->verificationLink = $msg['message'];
          $form_state->setRebuild();
          $this->step++;
        }
      }
    }
    else {
      $msg = $this->getAccessToken($store->get('credentials'), $store->get('name'), 2, $form_state->getValue('verification_code'));
      $this->messenger->addMessage($msg['message']);
    }
  }

  /**
   * Get Access token.
   */
  protected function getAccessToken($credentials_json, $application_name, $step = 1, $verification_code = '') {

    $store = $this->keyValue->get("dst_google_sheet_storage");
    try {
      $client = new \Google_Client();
      $client->setApplicationName(trim($application_name));
      $client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
      $client->setAuthConfig(json_decode(trim($credentials_json), TRUE));
      $client->setAccessType('offline');

      if ($step < 2) {
        $browser_link = $client->createAuthUrl();
        // Request authorization from the user.
        return [
          'message_type' => 'success',
          'message' => "<a href='" . $browser_link . "' target='_blank'>" . $browser_link . "</a>",
        ];

      }
      else {
        // Get an access token.
        $access_token = $client->fetchAccessTokenWithAuthCode($verification_code);
        $store->set('access_token', json_encode($access_token));
        return [
          'message_type' => 'success',
          'message' => $this->t("The credentials has been saved successfully."),
        ];
      }
    }
    catch (\Exception $exception) {
      $exception_message = $this->t('Exception occurred @exception', [
        '@exception' => $exception,
      ]);
      $this->logger->get('dst_entity_generate')->error($exception_message);
      return [
        'message_type' => 'error',
        'message' => $this->t("Invalid credentials."),
      ];
    }
  }

}
