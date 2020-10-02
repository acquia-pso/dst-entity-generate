<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class GoogleSheetApi to connect with Google Sheets.
 */
class GeneralApi {

  /**
   * Private variable to check debug mode.
   *
   * @var mixed
   */
  private $debugMode;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * KeyValue store having google sheet settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $googleSheetStorage;

  /**
   * KeyValue store having entity generate settings storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $entityGenerateStorage;

  /**
   * Messenger service definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Google Service Sheets definition.
   *
   * @var \Google_Service_Sheets
   */
  protected $googleSheetService;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GoogleSpreadsheetAccess object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory,
                              KeyValueFactoryInterface $key_value,
                              MessengerInterface $messenger,
                              GoogleSheetApi $googleSheetApi,
                              ConfigFactoryInterface $configFactory) {

    $this->logger = $logger_factory->get('dst_entity_generate');
    $this->googleSheetStorage = $key_value->get('dst_google_sheet_storage');
    $this->entityGenerateStorage = $key_value->get('dst_entity_generate_storage');
    $this->debugMode = $this->entityGenerateStorage->get('debug_mode');
    $this->messenger = $messenger;
    $this->googleSheetApi = $googleSheetApi;
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
  }

  public function can_sync_entity($entity) {
    $message = FALSE;
    if (!empty($this->syncEntities) && $this->syncEntities[strtolower($entity)]['All'] !== 'All') {
      $message = $this->t("Skipping, @entity entity sync is disabled.");
    }
    return $message;
  }
}
