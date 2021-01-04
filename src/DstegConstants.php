<?php

namespace Drupal\dst_entity_generate;

/**
 * Class DstegConstants have names of DST sheet tabs.
 *
 * @package Drupal\dst_entity_generate
 */
final class DstegConstants {

  // Constants containing sheet names.
  const BUNDLES = 'Bundles';
  const CONTENT_TYPES = 'Content types';
  const VOCABULARIES = 'Vocabularies';
  const PARAGRAPHS = 'Paragraph Types';
  const FIELDS = 'Fields';
  const MENUS = 'Menus';
  const USER_ROLES = 'User roles';
  const WORKFLOWS = 'Workflows';
  const WORKFLOW_TRANSITIONS = 'Workflow transitions';
  const WORKFLOW_STATES = 'Workflow states';
  const IMAGE_STYLES = 'Image styles';
  const IMAGE_EFFECTS = 'Image effects';
  const OVERVIEW = 'Overview';
  const SKIP_ENTITY_MESSAGE = 'Skipping: @entity entity sync is disabled.';
  /**
   * Variable FIELD_TYPES is used to hold meta data about field types.
   *
   * Array constructed with below patter:
   * '<Human readable field type>' = [
   *   'type' => '<field_machine_name>',
   *   'module_dependency' => '<required_module_machine_name>',
   * ]
   */
  const FIELD_TYPES = [
    'Text (plain)' => [
      'type' => 'string',
    ],
    'Text (plain, long)' => [
      'type' => 'string_long',
    ],
    'Text (formatted)' => [
      'type' => 'text',
    ],
    'Text (formatted, long)' => [
      'type' => 'text_long',
    ],
    'Text (formatted, long, with summary)' => [
      'type' => 'text_with_summary',
    ],
    'Boolean' => [
      'type' => 'boolean',
    ],
    'Image' => [
      'type' => 'image',
    ],
    'List (float)' => [
      'type' => 'list_float',
    ],
    'Date' => [
      'type' => 'datetime',
    ],
    'Date range' => [
      'type' => 'daterange',
      'module_dependency' => 'datetime_range',
    ],
    'Link' => [
      'type' => 'link',
      'module_dependency' => 'link',
    ],
  ];

}
