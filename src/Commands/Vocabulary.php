<?php

namespace Drupal\dst_entity_generate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dst_entity_generate\BaseEntityGenerate;
use Drupal\dst_entity_generate\DstegConstants;
use Drupal\dst_entity_generate\Services\GeneralApi;

/**
 * Class provides functionality of Vocabularies generation from DST sheet.
 *
 * @package Drupal\dst_entity_generate\Commands
 */
class Vocabulary extends BaseEntityGenerate {

  /**
   * {@inheritDoc}
   */
  protected $entity = 'vocabulary';

  /**
   * {@inheritDoc}
   */
  protected $dstEntityName = 'vocabularies';

  /**
   * Array of all dependent modules.
   *
   * @var array
   */
  protected $dependentModules = ['taxonomy'];

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DstegVocabulary constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type manager.
   * @param \Drupal\dst_entity_generate\Services\GeneralApi $generalApi
   *   The helper service for DSTEG.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GeneralApi $generalApi) {
    $this->entityTypeManager = $entityTypeManager;
    $this->helper = $generalApi;
  }

  /**
   * Generate Vocabularies from Drupal Spec tool sheet.
   *
   * @command dst:generate:vocabs
   * @aliases dst:v
   * @usage drush dst:generate:vocabs
   * @options update Update existing entities.
   */
  public function generateVocabularies($options = ['update' => FALSE]) {
    $this->io()->success('Generating Drupal Vocabularies.');
    // Call all the methods to generate the Drupal entities.
    $entity = 'taxonomy_term';
    $this->updateMode = $options['update'];
    $mode = 'create';
    if ($this->updateMode) {
      $mode = 'update';
    }
    $data = $this->getDataFromSheet(DstegConstants::BUNDLES);
    $vocab_types = $this->getVocabTypeData($data);
    $vocab_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabularies = $vocab_storage->loadMultiple();
    foreach ($vocab_types as $index => $vocab) {
      $vocab_name = $vocab['vid'];
      $vocan_url_alias = $vocab['url_alias_pattern'];
      $type = $vocab['type'];
      if ($vocabularies[$vocab['vid']]) {
        $this->generatePathautoPattern($vocab_name, $vocan_url_alias, $entity);
        if ($this->updateMode && $data[$index][$this->implementationFlagColumn] === $this->updateFlag) {
          $this->updateEntityType($vocabularies[$vocab['vid']], $vocab);
          $this->io()->success("Vocabulary $vocab_name updated.");
          continue;
        }
        $this->io()->warning("Vocabulary $vocab_name Already exists. Skipping creation...");
        continue;
      }
      $status = $vocab_storage->create($vocab)->save();
      if ($status === SAVED_NEW) {
        $this->io()->success("Vocabulary $type is successfully created...");
        $this->generatePathautoPattern($vocab_name, $vocan_url_alias, $entity);
      }
    }

    // Here goes the fields creation.
    $bundle_type = 'Vocabulary';
    $fields_data = $bundles_data = [];
    $fields_data = $this->getDataFromSheet(DstegConstants::FIELDS, FALSE);
    $fields_data = $this->filterEntityTypeSpecificData($fields_data, 'bundle');
    if (empty($fields_data)) {
      $this->io()->warning("There is no data from the sheet. Skipping Generating fields data for $bundle_type.");
      return self::EXIT_SUCCESS;
    }
    foreach ($vocab_types as $bundle) {
      $bundles_data[$bundle['name']] = $bundle['vid'];
    }
    $this->helper->generateEntityFields($bundle_type, $fields_data, $bundles_data, $mode);
  }

  /**
   * Get data needed for Vocabulary type entity.
   *
   * @param array $data
   *   Array of Vocabularies.
   *
   * @return array|null
   *   Vocabulary compliant data.
   */
  private function getVocabTypeData(array $data) {
    $vocab_types = [];
    foreach ($data as $item) {
      $vocabs = [];
      $description = isset($item['description']) ? $item['description'] : $item['name'] . ' vocabulary.';
      $vocabs['vid'] = $item['machine_name'];
      $vocabs['description'] = $description;
      $vocabs['name'] = $item['name'];
      $vocabs['url_alias_pattern'] = $item['url_alias_pattern'];

      \array_push($vocab_types, $vocabs);
    }
    return $vocab_types;

  }

}
