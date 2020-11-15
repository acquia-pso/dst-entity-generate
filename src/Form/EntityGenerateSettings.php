<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
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
      '#default_value' => isset($store) ? $store->get('debug_mode') : FALSE,
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
    $overview_records = $this->googleSheetApi->getData(DstegConstants::OVERVIEW);
    if (isset($overview_records) && !empty($overview_records)) {
      foreach ($overview_records as $overview) {
        if ($overview['total'] > 0) {
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
              if ($clean_spec !== 'Bundles') {
                $entity_list[$lower_clean_spec]['types'] = $this->getEntityOfEntityTypeOptionList($clean_spec);
              }
              else {
                $entity_list[$lower_clean_spec]['types'] = ['All' => 'All'];
              }
              $dst_entity_group = $lower_clean_spec;
            }
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

  /**
   * Helper function to generate option list.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   Return array of options.
   */
  public function getEntityOfEntityTypeOptionList($entity_type) {
    $data = $this->googleSheetApi->getData($entity_type);
    $options['All'] = 'All';
    if (isset($data) && !empty($data)) {
      switch ($entity_type) {
        case 'Fields':
          foreach ($data as $index => $field) {
            $machine_name = trim($field['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@field_label(Bundle: @bundle)", [
                '@field_label' => $field['field_label'],
                '@bundle' => $field['bundle'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Menus':
          foreach ($data as $index => $menu) {
            $machine_name = trim($menu['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@menu_title", [
                '@menu_title' => $menu['title'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Image styles':
          foreach ($data as $index => $image_style) {
            $machine_name = trim($image_style['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@image_style", [
                '@image_style' => $image_style['style_name'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Image effects':
          foreach ($data as $index => $image_effect) {
            $machine_name = trim($image_effect['effect'], '- ');
            if ($machine_name !== '') {
              $machine_name = $this->getMachineName($machine_name);
              $label = $this->t("@image_effect(Image style: @image_style)", [
                '@image_effect' => $image_effect['effect'],
                '@image_style' => $image_effect['image_style'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Workflows':
          foreach ($data as $index => $workflow) {
            $machine_name = trim($workflow['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@workflow(Type: @workflow_type)", [
                '@workflow' => $workflow['label'],
                '@workflow_type' => $workflow['type'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Workflow states':
          foreach ($data as $index => $workflow_state) {
            $machine_name = trim($workflow_state['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@workflow_state(Workflow: @workflow)", [
                '@workflow_state' => $workflow_state['label'],
                '@workflow' => $workflow_state['workflow'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'Workflow transitions':
          foreach ($data as $index => $workflow_transition) {
            $machine_name = trim($workflow_transition['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@transition_label(Workflow: @workflow, From state: @from, To state: @to)", [
                '@transition_label' => $workflow_transition['label'],
                '@workflow' => $workflow_transition['workflow'],
                '@from' => $workflow_transition['from_state'],
                '@to' => $workflow_transition['to_state'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

        case 'User roles':
          foreach ($data as $index => $user_role) {
            $machine_name = trim($user_role['machine_name'], '- ');
            if ($machine_name !== '') {
              $label = $this->t("@user_role", [
                '@user_role' => $user_role['name'],
              ]);
              $options[$index . '_' . $machine_name] = $label;
            }
          }
          break;

      }
    }
    return $options;
  }

  /**
   * Generates a machine name from a string.
   *
   * @param string $string
   *   String to generate machine name.
   *
   * @return string
   *   Machine name string.
   */
  protected function getMachineName(string $string) {
    $transliterated = $this->transliteration->transliterate($string, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);
    $transliterated = preg_replace('@[^a-z0-9_.]+@', '_', $transliterated);
    return $transliterated;
  }

}
