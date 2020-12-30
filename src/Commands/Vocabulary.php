<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\field\Entity\FieldConfig;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class provides functionality of Vocabularies generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Vocabulary extends BaseEntityGenerate {

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(GoogleSheetApi $sheet,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              GeneralApi $generalApi,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct($sheet, $generalApi, $configFactory);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
  }

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   */
  public function generateVocabularies() {
    $result = FALSE;
    $bundle_type = 'Vocabulary';
    try {
      $this->say($this->t('Generating Drupal Vocabularies.'));
      $bundles = $this->sheet->getData(DstegConstants::BUNDLES);
      foreach ($bundles as $bundle) {
        if ($bundle['type'] === $bundle_type && $bundle['x'] === 'w') {
          $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
          $vocabularies = $vocab_storage->loadMultiple();
          if (!isset($vocabularies[$bundle['machine_name']])) {
            $description = isset($bundle['description']) ? $bundle['description'] : $bundle['name'] . ' vocabulary.';
            $status = $vocab_storage->create([
              'vid' => $bundle['machine_name'],
              'description' => $description,
              'name' => $bundle['name'],
            ])->save();
            if ($status === 1) {
              $success_message = $this->t('Vocabulary @vocab is created.', [
                '@vocab' => $bundle['name'],
              ]);
              $this->say($success_message);
              $this->logger->info($success_message);
            }
          }
          else {
            $present_message = $this->t('Vocabulary @vocab is already present.', [
              '@vocab' => $bundle['name'],
            ]);
            $this->say($present_message);
            $this->logger->info($present_message);
          }
        }
      }
      // Generate fields now.
      $result = $this->generateEntityFields($bundle_type);
    }
    catch (\Exception $exception) {
      $this->displayAndLogException($exception, DstegConstants::VOCABULARIES);
      $result = self::EXIT_FAILURE;
    }

    return CommandResult::exitCode($result);
  }

  /**
   * Helper function to generate fields.
   */
  public function generateFields() {
    $result = self::EXIT_SUCCESS;

    $this->logger->notice($this->t('Generating Drupal Fields.'));
    // Call all the methods to generate the Drupal entities.
    $fields_data = $this->sheet->getData(DstegConstants::FIELDS);
    $bundles_data = $this->sheet->getData(DstegConstants::BUNDLES);
    $bundleArr = [];
    foreach ($bundles_data as $bundle) {
      if ($bundle['type'] === 'Vocabulary') {
        $bundleArr[$bundle['name']] = $bundle['machine_name'];
      }
    }
    if (!empty($fields_data)) {
      foreach ($fields_data as $field) {
        if ($field['x'] !== 'w') {
          continue;
        }
        $bundle = $field['bundle'];
        $bundle_name = trim(substr($bundle, 0, strpos($bundle, "(")));
        if (array_key_exists($bundle_name, $bundleArr)) {
          $bundleVal = $bundleArr[$bundle_name];
        }
        if (isset($bundleVal)) {
          try {
            $entity_type_id = 'taxonomy_vocabulary';
            $entity_type = 'taxonomy_term';
            $drupal_field = FieldConfig::loadByName($entity_type, $bundleVal, $field['machine_name']);

            // Skip field if present.
            if (!empty($drupal_field)) {
              $this->logger->notice($this->t(
                'The field @field is present in @vocab. Skipping.',
                [
                  '@field' => $field['machine_name'],
                  '@vocab' => $bundleVal,
                ]
              ));
              continue;
            }
            // Create field storage.
            $result = $this->helper->fieldStorageHandler($field, $entity_type);
            if ($result) {
              $this->helper->addField($bundleVal, $field, $entity_type_id, $entity_type);
            }
          }
          catch (\Exception $exception) {
            $this->displayAndLogException($exception, DstegConstants::FIELDS);
            $result = self::EXIT_FAILURE;
          }
        }
      }
    }
    return CommandResult::exitCode($result);
  }

}
