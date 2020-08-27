<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Class DstGoogleSheetSetting.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class DstGoogleSheetSetting extends ConfigFormBase {

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

    $form['import_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Import entities'),
      '#options' => $this->getEntityOptions(),
      '#default_value' => $store->get('import_entities'),
      '#description' => $this->t('Choose which entities to import.'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#description' => $this->t('Give Application Name like \'Google Sheets API Application\'.'),
      '#default_value' => $store->get('name'),
    ];

    $form['spreadsheet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spreadsheet'),
      '#description' => $this->t('Add unique id of spreadsheet. Example - %example', [
        '%example' => '1xJFEeIqTAC-Au02PEwPVS1zLLnwhsYaqqYPsbF8fv30',
      ]),
      '#default_value' => $store->get('spreadsheet'),
    ];

    $form['credentials'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Credentials'),
      '#description' => $this->t('Add json of google access credentials.'),
      '#default_value' => $store->get('credentials'),
    ];

    $form['access_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('Add json of access token.'),
      '#default_value' => $store->get('access_token'),
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
    $store->set('spreadsheet', $form_state->getValue('spreadsheet'));
    $store->set('import_entities', $form_state->getValue('import_entities'));
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

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames()
  {
    // TODO: Implement getEditableConfigNames() method.
  }
}
