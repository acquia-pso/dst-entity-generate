<?php

namespace Drupal\dst_entity_generate\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GoogleSheetApi;

/**
 * Drush command to generate image style.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class DstegImageStyle extends BaseEntityGenerate {
  use StringTranslationTrait;
  /**
   * Google Sheet Api service definition.
   *
   * @var \Drupal\dst_entity_generate\Services\GoogleSheetApi
   */
  protected $googleSheetApi;

  /**
   * Logger service definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The EntityType Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config array.
   *
   * @var array
   */
  protected $syncEntities;

  /**
   * DstCommands constructor.
   *
   * @param \Drupal\dst_entity_generate\Services\GoogleSheetApi $googleSheetApi
   *   Google Sheet Api service definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   LoggerChannelFactory service definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityType Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(GoogleSheetApi $googleSheetApi,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->googleSheetApi = $googleSheetApi;
    $this->logger = $loggerChannelFactory->get('dst_entity_generate');
    $this->syncEntities = $configFactory->get('dst_entity_generate.settings')->get('sync_entities');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Generate the Drupal image style from Drupal Spec tool sheet.
   *
   * @command dst:generate:imagestyle
   * @aliases dst:generate:dst:generate:imagestyle dst:f
   * @usage drush dst:generate:imagestyle
   */
  public function generateImageStyle() {
    $imageStyleSync = $this->syncEntities['images_styles'];
    if ($imageStyleSync['All'] === 'All') {
      try {
        $this->say($this->t('Generating Drupal Image Style.'));
        $imageStyle_data = $this->googleSheetApi->getData(DstegConstants::IMAGE_STYLES);
        if (!empty($imageStyle_data)) {

          // Call all the methods to generate the Drupal image style.
          foreach ($imageStyle_data as $imageStyle) {
            // Create image style only if it is in Wait and implement state.
            if ($imageStyle['x'] === 'w') {
              $sized_image = $this->entityTypeManager->getStorage('image_style')->load($imageStyle['machine_name']);
              if ($sized_image === NULL || empty($sized_image)) {
                // Create image style.
                $style = $this->entityTypeManager->getStorage('image_style')->create([
                  'name' => $imageStyle['machine_name'],
                  'label' => $imageStyle['style_name'],
                ]);

                $style->save();

                if ($style === 1) {
                  $message = $this->t('New image style @imagestyle created.', [
                    '@imagestyle' => $imageStyle['machine_name'],
                  ]);
                  $this->say($message);
                  $this->logger->info($message);
                }

              }
              else {
                $imageStyle_exist = $this->t('Image style @imagestyle already present.', [
                  '@imagestyle' => $imageStyle['machine_name'],
                ]);
                $this->say($imageStyle_exist);
                $this->logger->info($imageStyle_exist);
              }
            }

          }
        }

        return CommandResult::exitCode(self::EXIT_SUCCESS);
      }
      catch (\Exception $exception) {
        $this->yell($this->t('Exception occurred @exception', [
          '@exception' => $exception,
        ]));
        $this->logger->error('Exception occurred @exception', [
          '@exception' => $exception,
        ]);
        return CommandResult::exitCode(self::EXIT_FAILURE);
      }
    }
    else {
      $this->yell('Image Style sync is disabled, Skipping.');
    }
  }

}
