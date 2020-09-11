<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityGenerateSettings.
 *
 * @package Drupal\dst_entity_generate\Form
 */
final class EntityGenerateSettings extends ConfigFormBase {

  /**
   * Config settings name.
   */
  public const SETTINGS = 'dst_entity_generate.settings';

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * KeyValue store interface.
   *
   * @var KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * GoogleSheetApi definition.
   *
   * @var GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * Constructs a DstEntityGenerateSettings object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   * @param KeyValueFactoryInterface $key_value_factory
   *   The Key Value Factory definition.
   * @param GoogleSheetApi $google_sheet_api
   *   The GoogleSheetApi definition.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              KeyValueFactoryInterface $key_value_factory,
                              GoogleSheetApi $google_sheet_api) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value_factory;
    $this->googleSheetApi = $google_sheet_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('dst_entity_generate.google_sheet')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dst_entity_generate_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this
      ->config(self::SETTINGS);

    $entity_list_items = $this->getEntityList();
    if (is_array($entity_list_items) && !empty($entity_list_items)) {
      // Get sync entities from configuration.
      $sync_entities = $config->get('sync_entities');

      // Create fieldset for each entity type.
      foreach ($entity_list_items as $entity_key => $entity_item) {
        $form[$entity_key] = [
          '#type' => 'details',
          '#title' => $entity_item['label'],
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        $form[$entity_key]['sync_entities_' . $entity_key] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Select entity types'),
          '#options' => $entity_item['types'],
          '#default_value' => isset($sync_entities[$entity_key]) ? $sync_entities[$entity_key] : [],
          '#description' => $this->t('Select @type to sync from the Drupal spec tool sheet.', [
            '@type' => $entity_item['label'],
          ]),
        ];
      }
    }

    $store = $this->keyValue->get('dst_entity_generate_storage');
    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#default_value' => isset($store) ? $store->get('debug_mode') : false,
      '#description' => $this->t('Check this box to see connection messages. Keep it disabled in live environments.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_values = $form_state->getValues();
    $sync_entity_list = [];
    foreach ($form_values as $entity_key => $entity_types) {
      if (str_starts_with($entity_key, 'sync_entities_')) {
        // Remove prefix before save.
        $entity_name = str_replace('sync_entities_', '', $entity_key);
        foreach ($entity_types as $type_key => $type_value) {
          // Make list to be saved on config.
          $sync_entity_list[$entity_name][$type_key] = $type_value;
        }
      }
    }

    // Save sync entities in config.
    $this->config(self::SETTINGS)
      ->set('sync_entities', $sync_entity_list)
      ->save();

    $store = $this->keyValue->get("dst_entity_generate_storage");
    $store->set('debug_mode', $form_state->getValue('debug_mode'));
  }

  /**
   * Get Entity List as options for Sync Entities field.
   *
   * @return array
   *   Entity List of entities from sheet.
   */
  private function getEntityList() {
    $dst_entity_group = '';
    $entity_list = [];
    $overview_records = $this->googleSheetApi->getData('Overview');
    if (isset($overview_records) && !empty($overview_records)) {
      foreach ($overview_records as $overview) {
        $clean_spec = trim($overview['specification']);
        if (!empty($clean_spec)) {
          if (str_starts_with($clean_spec, '-')) {
            $clean_spec = trim($clean_spec, '- ');
            $entity_list[$dst_entity_group]['types'][$clean_spec] = $clean_spec;
          }
          else {
            $lower_clean_spec = preg_replace('/\s+/', '_', strtolower($clean_spec));
            $entity_list[$lower_clean_spec] = [
              'label' => $clean_spec,
              'types' => ['All' => 'All'],
            ];
            $dst_entity_group = $lower_clean_spec;
          }
        }
      }
    }
    return $entity_list;
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames()
  {
    return [
      static::SETTINGS,
    ];
  }
}
