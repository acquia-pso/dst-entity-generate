<?php

namespace Drupal\dst_entity_generate\Commands;

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
  protected $entity = 'paragraph_type';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityMame = 'paragraph_types';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['paragraphss'];

  /**
   * Generate all the Drupal entities from Drupal Spec tool sheet.
   *
   * @command dst:generate:paragraphs
   * @aliases dst:para dst:p
   */
  public function generateParagraph() {
    $this->say($this->t('Generating Drupal Paragraphs.'));
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    \var_dump($data);
  }

}
