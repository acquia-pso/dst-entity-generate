<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Google Api Settings Config Form.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class GoogleSheetApiSetting extends ConfigFormBase {

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
   * Constructs a DstGoogleSheetSetting object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValueFactory
   *   The key value store.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $keyValueFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $keyValueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('keyvalue')
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

    $form['credentials'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Credentials'),
      '#required' => TRUE,
      '#description' => $this->t('Add json of google access credentials.'),
      '#default_value' => (isset($store) && !empty($store->get('credentials'))) ? $store->get('credentials') : '',
    ];

    $form['access_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Access Token'),
      '#required' => TRUE,
      '#description' => $this->t("Add json of access token. Use drush command 'drush dst:get:access_token' and follow the instructions to get access token."),
      '#default_value' => (isset($store) && !empty($store->get('access_token'))) ? $store->get('access_token') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $store = $this->keyValue->get("dst_google_sheet_storage");

    $store->set('name', $form_state->getValue('name'));
    $store->set('credentials', $form_state->getValue('credentials'));
    $store->set('access_token', $form_state->getValue('access_token'));
    $store->set('spreadsheet_id', $form_state->getValue('spreadsheet_id'));
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    // @TODO: Implement getEditableConfigNames() method.
  }

}
