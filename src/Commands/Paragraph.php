<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;

/**
 * Class provides functionality of Content types generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Paragraph extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'paragraph_types';

  /**
   * Module handler for module related operations.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructor function.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;

  }

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:paragraphs
   * @aliases dst:para dst:p
   */
  public function generateParagraph() {
    $this->say($this->t('Generating Drupal Paragraphs.'));
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
  }

  /**
   * Validate hook for all validations.
   *
   * @hook validate dst:generate:paragraphs
   * @throws \Exception
   */
  public function validate() {
    // Check if paragrpah module exists and enabled.
    if (!$this->moduleHandler->moduleExists('paragraphs')) {
      throw new \Exception("Paragraph module is not enabled. Please enable to generate paragraph types.");
    }
  }

}
