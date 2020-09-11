<?php

namespace Drupal\dst_entity_generate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
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

    $sync_entities = $config->get('sync_entities');
    $form['sync_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select entities'),
      '#options' => $this->getEntityOptions(),
      '#default_value' => isset($sync_entities) ? $sync_entities : '',
      '#description' => $this->t('Select entities to sync from the Drupal spec tool sheet.'),
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
      ->set('sync_entities', $form_values['sync_entities'])
      ->save();

    $store = $this->keyValue->get("dst_entity_generate_storage");
    $store->set('debug_mode', $form_state->getValue('debug_mode'));
  }

  /**
   * Helper function to get entity options.
   */
  private function getEntityOptions() {
    $sync_entities_list = [
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
      if (in_array($entity_id, $sync_entities_list)) {
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
