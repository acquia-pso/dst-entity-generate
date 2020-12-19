<?php

namespace Drupal\dst_entity_generate;

use Consolidation\AnnotatedCommand\CommandResult;
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
   * BaseEntityGenerate constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $sheet
   *   GoogleSheetApi service class object.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(GoogleSheetApi $sheet, GeneralApi $generalApi) {
    parent::__construct();
    $this->sheet = $sheet;
    $this->helper = $generalApi;
  }

  /**
   * Validate hook for commands.
   *
   * @hook validate
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
   * Helper function to display skip message and exit command.
   *
   * @param string $entity
   *   Entity that is not sync enabled.
   *
   * @return \Consolidation\AnnotatedCommand\CommandResult
   *   Command exit code.
   */
  public function displaySkipMessage(string $entity) {
    $message = $this->t(DstegConstants::SKIP_ENTITY_MESSAGE,
      ['@entity' => $entity]
    );
    // @todo yell() and say() needs to be part of the `logMessage() method`.
    $this->yell($message, 100, 'yellow');
    return CommandResult::exitCode(self::EXIT_SUCCESS);
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
