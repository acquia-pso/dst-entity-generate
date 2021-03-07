<?php

namespace Drupal\dst_entity_generate\Services;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\media\MediaSourceManager;

/**
 * Optional dependency handler.
 */
class OptionalDependencyHandler {

  /**
   * Media source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $sourceManager;

  /**
   * Sets optional MediaSourceManager dependency
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $source_manager
   *   Media source plugin manager.
   *
   * @return $this
   */
  public function setMediaSourceManager(PluginManagerInterface $source_manager) {
    $this->sourceManager = $source_manager;
    return $this;
  }

  /**
   * Returns MediaSourceManager if available.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\media\MediaSourceManager|null
   */
  public function getMediaSourceManager() {
    if (isset($this->sourceManager) && $this->sourceManager instanceof MediaSourceManager) {
      return $this->sourceManager;
    }
    return NULL;
  }

}
