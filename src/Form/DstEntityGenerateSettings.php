<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DstEntityGenerateSettings.
 *
 * @package Drupal\dst_entity_generate\Form
 */
class DstEntityGenerateSettings extends ConfigFormBase {

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
   * Constructs a DstEntityGenerateSettings object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type_manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              KeyValueFactoryInterface $key_value_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value_factory;
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
    return 'dst_entity_generate_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this
      ->config(self::SETTINGS);

    $import_entities = $config->get('import_entities');
    $form['import_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select entities'),
      '#options' => $this->getEntityOptions(),
      '#default_value' => isset($import_entities) ? $import_entities : '',
      '#description' => $this->t('Select entities to import from the Drupal spec tool sheet.'),
    ];

    $store = $this->keyValue->get('dst_entity_generate_storage');
    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#default_value' => $store->get('debug_mode'),
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
    $this->config(self::SETTINGS)
      ->set('import_entities', $form_values['import_entities'])
      ->save();

    // Store debug mode in store as it is not needed to be exported.
    $store = $this->keyValue->get("dst_entity_generate_storage");
    $store->set('debug_mode', $form_state->getValue('debug_mode'));
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
    return [
      static::SETTINGS,
    ];
  }
}
