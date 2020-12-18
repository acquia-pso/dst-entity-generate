<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity Generate Config Form.
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
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * GoogleSheetApi definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * The transliteration helper.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a DstEntityGenerateSettings object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The Key Value Factory definition.
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $google_sheet_api
   *   The GoogleSheetApi definition.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              KeyValueFactoryInterface $key_value_factory,
                              GoogleSheetApi $google_sheet_api,
                              TransliterationInterface $transliteration) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value_factory;
    $this->googleSheetApi = $google_sheet_api;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('dst_entity_generate.google_sheet_api'),
      $container->get('transliteration')
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
      $form['sync_entities'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select Entity Types'),
        '#options' => $entity_list_items,
        '#default_value' => isset($sync_entities) ? $sync_entities : [],
        '#description' => $this->t('Select entity type to sync from the Drupal spec tool sheet.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_values = $form_state->getValues();

    // Save sync entities in config.
    $this->config(self::SETTINGS)
      ->set('sync_entities', $form_values['sync_entities'])
      ->save();
  }

  /**
   * Get Entity List as options for Sync Entities field.
   *
   * @return array
   *   Entity List of entities from sheet.
   */
  private function getEntityList() {
    $entity_list = [];
    $skip_entity_listing = [
      'Bundles',
      'Fields',
      'Workflow states',
      'Workflow transitions',
    ];
    $overview_records = $this->googleSheetApi->getData(DstegConstants::OVERVIEW);
    if (isset($overview_records) && !empty($overview_records)) {
      foreach ($overview_records as $overview) {
        if ($overview['total'] > 0) {
          $clean_spec = trim($overview['specification']);
          if (in_array($clean_spec, $skip_entity_listing)) {
            continue;
          }
          if (!empty($clean_spec)) {
            if (str_starts_with($clean_spec, '-')) {
              $clean_spec = trim($clean_spec, '- ');
            }
            $lower_clean_spec = preg_replace('/\s+/', '_', strtolower($clean_spec));
            $entity_list[$lower_clean_spec] = $clean_spec;
          }
        }
      }
    }
    return $entity_list;
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

}
