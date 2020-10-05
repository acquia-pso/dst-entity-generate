<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class GoogleSheetApi to connect with Google Sheets.
 */
class GeneralApi {

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * Entity type manager service definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  use StringTranslationTrait;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
                              KeyValueFactoryInterface $key_value,
                              ConfigFactoryInterface $configFactory,
                              EntityTypeManagerInterface $entityTypeManager) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $this->entityGenerateStorage = $key_value->get('dst_entity_generate_storage');
    $this->debugMode = $this->entityGenerateStorage->get('debug_mode');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * The function to decide whether the sync of the Entity supplied is possible.
   *
   * @param string $entity
   *   The entity type to check.
   *
   * @return bool
   *   Returns TRUE if the entity should be skipped.
   */
  public function skipEntitySync(string $entity) {
    $skipEntitySync = FALSE;
    $entity = strtolower(str_replace(" ", "_", $entity));
    if (!empty($this->syncEntities) && $this->syncEntities[$entity]['All'] !== 'All') {
      $skipEntitySync = TRUE;
    }
    return $skipEntitySync;
  }

  /**
   * If "Debug mode" is on, log a message using the logger.
   *
   * @param array $message
   *   Message which needs to be logged.
   */
  public function logMessage(array $message) {
    if ($this->debugMode) {
      $this->logger->debug(implode("<br />", $message));
    }
  }

  /**
   * Helper function to get all the existing entities.
   *
   * @param string $entity_type
   *   The entity type for which we require to load the entities for.
   * @param string $loading_type
   *   The method supports loading just entity types or multiple.
   *
   * @return mixed
   *   Return the multiple entities which got loaded.
   */
  public function getAllEntities(string $entity_type, string $loading_type = 'default') {
    if ($loading_type === 'all') {
      $results = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
    }
    else {
      $results = $this->entityTypeManager->getStorage($entity_type);
    }
    return $results;
  }

}
