<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;

/**
 * Google Api Settings Form.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class GoogleSheetApiSetting extends FormBase {

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
    $file_private_path = Settings::get('file_private_path', '');

    if (empty($file_private_path)) {
      $form['private_directory_message'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--error"><b>' . $this->t('Private file system is not configured. Refer "Private file path" section in settings.php file to configure it.') . '</b></div>',
      ];
    }

    if ($this->step === 1) {
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

      $form['spreadsheet_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Google Sheet Id'),
        '#required' => TRUE,
        '#description' => $this->t(
          'Copy the spreadsheet unique id from google sheet url and paste in this field. It looks like 1xJOEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30.'
        ),
        '#default_value' => (isset($store) && !empty($store->get('spreadsheet_id'))) ? $store->get('spreadsheet_id') : '',
      ];

      $form['credentials_json_file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload credentials.json File'),
        '#required' => TRUE,
        '#description' => '<p>' . $this->t('To get credentials.json file, go to') . ' <a href="https://developers.google.com/sheets/api/quickstart/php" rel="nofollow noindex noopener external ugc" target="_blank">' . $this->t('Google API Credentials') . '</a> ' . $this->t('and just complete step 1 and follow the following steps.') . '</p><p>' . $this->t('Enter new project name -> Configure your OAuth client: Desktop App -> Finally click on Create button') . '</p>',
        '#upload_validators' => ['file_validate_extensions' => ['json']],
        '#upload_location' => 'private://google_credentials',
        '#default_value' => (isset($store) && !empty($store->get('credentials_json_file'))) ? $store->get('credentials_json_file') : '',
      ];
    }

    if ($this->step === 2) {

      $form['step'] = [
        '#type' => 'item',
        '#markup' => "<b>Step - 2</b>",
      ];

      if (!empty($this->verificationLink)) {
        $form['verification_link'] = [
          '#type' => 'item',
          '#markup' => $this->t("Authorization to access Google Spreadsheet needed.  To get the verification code") . ' ' . $this->verificationLink . '.',
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

    if (!empty($file_private_path)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $button_label,
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $store = $this->keyValue->get("dst_google_sheet_storage");
    if ($this->step < 2) {
      $credentials_json_file = $form_state->getValue('credentials_json_file');
      $file = $this->entityTypeManager->getStorage('file')->load($credentials_json_file[0]);
      $credential_data = trim(file_get_contents($file->getFileUri()));
      $name = trim($form_state->getValue('name'));
      if (empty($credential_data)) {
        $this->logger->get('dst_entity_generate')->error($this->t('Invalid credentials.json File. Please try again.'));
      }
      else {
        $store->set('credentials', $credential_data);
        $store->set('name', $name);
        $store->set('spreadsheet_id', $form_state->getValue('spreadsheet_id'));
        $store->set('credentials_json_file', $credentials_json_file);

        if (!empty($credential_data) && !empty($name)) {
          $msg = $this->getAccessToken($credential_data, $name, 1);
          if ($msg['message_type'] === 'error') {
            $this->messenger->addError($msg['message']);
          }
          else {
            $this->verificationLink = $msg['message'];
            $form_state->setRebuild();
            $this->step++;
          }
        }
      }
    }
    else {
      $msg = $this->getAccessToken($store->get('credentials'), $store->get('name'), 2, $form_state->getValue('verification_code'));
      $this->messenger->addMessage($msg['message']);
      // Redirection to General Tab.
      $form_state->setRedirect('dst_entity_generate.settings');
      return;
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
          'message' => "<a href='" . $browser_link . "' target='_blank'>" . $this->t('click here') . "</a>",
        ];

      }
      else {
        // Get an access token.
        $access_token = $client->fetchAccessTokenWithAuthCode($verification_code);
        $store->set('access_token', json_encode($access_token));
        return [
          'message_type' => 'success',
          'message' => $this->t("The credentials has been saved successfully. Please configure the following general settings."),
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
        'message' => $this->t("Invalid credentials.json File. Please try again."),
      ];
    }
  }

}
