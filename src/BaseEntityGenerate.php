<?php

namespace Drupal\dst_entity_generate;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\Services\GeneralApi;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;
use Drush\Commands\DrushCommands;

/**
 * Base class for all entity generate commands.
 */
abstract class BaseEntityGenerate extends DrushCommands {

  use StringTranslationTrait;

  /**
   * GoogleSheetApi service class object.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $sheet;

  /**
   * Helper class for entity generation.
   *
   * @var \Drupal\dst_entity_generate\Services\GeneralApi
   */
  protected $helper;

  /**
   * Sync configuration array.
   *
   * @var array|mixed|null
   */
  private $syncEntities;

  /**
   * BaseEntityGenerate constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(GoogleSheetApi $sheet, GeneralApi $generalApi, ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->helper = $generalApi;
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
  }

  /**
   * Validate hook for commands.
   *
   * @hook validate
   * @throws \Exception
   */
  public function validateGoogleSheetCreds() {
    $keyValueStorage = \Drupal::service('keyvalue');

    $googleSheetStorage = $keyValueStorage->get('dst_google_sheet_storage');

    $requiredConfigs = ['name', 'credentials', 'access_token', 'spreadsheet_id'];

    foreach ($requiredConfigs as $config) {
      if (empty($googleSheetStorage->get($config))) {
        throw new \Exception("Please configure $config in google sheet credentials configurations.");
      }
    }
  }

  /**
   * Validate whether sync is enabled or not.
   *
   * @hook validate
   * @throws \Exception
   */
  public function validateEntitySync(CommandData $commandData) {
    $entity_type = '';
    $command = $commandData->annotationData()->get('command');
    switch ($command) {
      case 'dst:generate:bundles':
        $entity_type = DstegConstants::CONTENT_TYPES;
        break;

      case 'dst:generate:vocabs':
        $entity_type = DstegConstants::VOCABULARIES;
        break;

      case 'dst:generate:image-effects':
        $entity_type = DstegConstants::IMAGE_EFFECTS;
        break;

      case 'dst:generate:menus':
        $entity_type = DstegConstants::MENUS;
        break;

      case 'dst:generate:user-roles':
        $entity_type = DstegConstants::USER_ROLES;
        break;

      case 'dst:generate:workflow':
        $entity_type = DstegConstants::WORKFLOWS;
        break;
    }
    if (!empty($entity_type)) {
      $skipEntitySync = $this->helper->skipEntitySync($entity_type);
      if ($skipEntitySync) {
        $message = $this->t(DstegConstants::SKIP_ENTITY_MESSAGE,
          ['@entity' => $entity_type]
        );
        throw new \Exception($message);
      }
    }
  }

  /**
   * Helper function to display and log exception.
   *
   * @param \Exception $exception
   *   Exception object.
   * @param string $entity
   *   Entity name on which exception occurred.
   */
  public function displayAndLogException(\Exception $exception, string $entity) {
    $message = $this->t('Exception occurred while generating @entity: @exception', [
      '@exception' => $exception->getMessage(),
      '@entity' => $entity,
    ]);
    $this->yell($message);
    $this->logger->error($message);
  }

}
