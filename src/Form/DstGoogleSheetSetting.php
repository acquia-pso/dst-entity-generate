<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleSpreadSheetSettings.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class DstGoogleSheetSetting extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'dst_google_sheet.setting';

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WsfAnalyticsDataLayerSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Locale\CountryManager $country_manager
   *   The path alias manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

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
    return 'dst_google_sheet_setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['import_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Import entities'),
      '#options' => $this->getEntityOptions(),
      '#default_value' => $config->get('import_entities'),
      '#description' => $this->t('Choose which entities to import.'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#description' => $this->t('Give Application Name.'),
      '#default_value' => $config->get('name'),
    ];

    $form['spreadsheet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spreadsheet'),
      '#description' => $this->t('Add unique id of spreadsheet. Example - %example', [
        '%example' => '1xJFEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30',
      ]),
      '#default_value' => $config->get('spreadsheet'),
    ];

    $form['credentials_info'] = [
      '#markup' => $this->t('If you are using ACE/ACSF, please add Google credentials and access_token in the secret.settings.php.'),
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
      ->set('import_entities', $form_state->getValue('import_entities'))
      ->save();

  }

  /**
   * Helper function to get entity options.
   */
  private function getEntityOptions() {
    $import_entities_list = [
      'image_style',
      'menu_link_content',
      'node_type',
      'menu',
      'taxonomy_vocabulary',
      'user_role',
    ];
    $entity_list = [];
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($entity_definitions as $entity_id => $entity_definition) {
      if (in_array($entity_id, $import_entities_list)) {
        $entity_list[$entity_id] = $entity_definition->getLabel();
      }
    }
    return $entity_list;
  }

}
