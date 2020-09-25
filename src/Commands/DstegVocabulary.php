<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class to provide functionality to generate Vocabulary.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegVocabulary extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GoogleSheetApi service class object.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DstegBundle constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The Key Value Factory definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(GoogleSheetApi $sheet, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, KeyValueFactoryInterface $key_value, ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->debugMode = $key_value->get('dst_entity_generate_storage')->get('debug_mode');
    $this->configFactory = $config_factory;
  }

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   */
  public function generateVocabularies() {
    $this->say($this->t('Generating Drupal Vocabularies.'));
    $sync_entities = $this->configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    if ($sync_entities && array_key_exists('bundles', $sync_entities) && $sync_entities['bundles']['Vocabularies'] === 'Vocabularies') {
      try {
        $bundles = $this->sheet->getData(DstegConstants::BUNDLES);
        foreach ($bundles as $bundle) {
          if ($bundle['type'] === 'Vocabulary' && $bundle['x'] === 'w') {
            $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
            $vocabularies = $vocab_storage->loadMultiple();
            if (!isset($vocabularies[$bundle['machine_name']])) {
              $result = $vocab_storage->create([
                'vid' => $bundle['machine_name'],
                'description' => isset($bundle['description']) ? $bundle['description'] : '',
                'name' => $bundle['name'],
              ])->save();
              if ($result === 1) {
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
        return CommandResult::exitCode(self::EXIT_SUCCESS);
      }
      catch (\Exception $exception) {
        if ($this->debugMode) {
          $exception_message = $this->t('Exception occurred @exception.', [
            '@exception' => $exception->getMessage(),
          ]);
          $this->yell($exception_message);
          $this->logger->error($exception_message);
        }
        else {
          $exception_message = $this->t('Error occurred while processing Vocabularies.');
          $this->yell($exception_message);
          $this->logger->error($exception_message);
        }
        return CommandResult::exitCode(self::EXIT_FAILURE);
      }
    }
  }

}
