<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoogleSpreadSheetSettings.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class GoogleSpreadSheetSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'google_spreadsheet.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_spreadsheet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#description' => $this->t('Give Application Name.'),
      '#default_value' => $config->get('name'),
    ];

    $form['credentials'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Credentials'),
      '#description' => $this->t('Add json of google access credentials.'),
      '#default_value' => $config->get('credentials'),
    ];

    $form['access_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('Add json of access token.'),
      '#default_value' => $config->get('access_token'),
    ];

    $form['spreadsheet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spreadsheet'),
      '#description' => $this->t('Add unique id of spreadsheet. Example - 1xJFEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30'),
      '#default_value' => $config->get('spreadsheet'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('name', $form_state->getValue('name'))
      ->set('credentials', $form_state->getValue('credentials'))
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('spreadsheet', $form_state->getValue('spreadsheet'))
      ->save();

  }

}
