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
  const MEDIA_TYPES = 'Media Types';
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
    'Number (decimal)' => [
      'type' => 'decimal',
    ],
    'Number (float)' => [
      'type' => 'float',
    ],
    'Number (integer)' => [
      'type' => 'integer',
    ],
    'Boolean' => [
      'type' => 'boolean',
    ],
    'Image' => [
      'type' => 'image',
    ],
    'List (float)' => [
      'type' => 'list_float',
      'dependencies' => [
        'required' => [
          'settings' => [
            'allowed_values',
          ],
        ],
      ],
    ],
    'List (integer)' => [
      'type' => 'list_integer',
      'dependencies' => [
        'required' => [
          'settings' => [
            'allowed_values',
          ],
        ],
      ],
    ],
    'Date' => [
      'type' => 'datetime',
    ],
    'Date range' => [
      'type' => 'daterange',
      'dependencies' => [
        'required' => [
          'module' => 'datetime_range',
        ],
      ],
    ],
    'Link' => [
      'type' => 'link',
      'dependencies' => [
        'required' => [
          'module' => 'link',
        ],
      ],
    ],
    'Email' => [
      'type' => 'email',
    ],
    'Telephone number' => [
      'type' => 'telephone',
      'dependencies' => [
        'required' => [
          'module' => 'telephone',
        ],
      ],
    ],
    'Entity reference' => [
      'type' => 'entity_reference',
      'dependencies' => [
        'required' => [
          'settings' => [
            'target_type',
          ],
        ],
        'optional' => [
          'settings' => [
            'handler_settings' => [
              'target_bundles',
            ],
          ],
        ],
      ],
    ],
    'Fieldset' => [
      'type' => 'field_group',
      'format_type' => 'fieldset',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Details' => [
      'type' => 'field_group',
      'format_type' => 'details',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Details sidebar' => [
      'type' => 'field_group',
      'format_type' => 'details_sidebar',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'HTML element' => [
      'type' => 'field_group',
      'format_type' => 'html_element',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Tab' => [
      'type' => 'field_group',
      'format_type' => 'tab',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Tabs' => [
      'type' => 'field_group',
      'format_type' => 'tabs',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Accordion' => [
      'type' => 'field_group',
      'format_type' => 'accordion',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Accordion Item' => [
      'type' => 'field_group',
      'format_type' => 'accordion_item',
      'dependencies' => [
        'required' => [
          'module' => 'field_group',
        ],
      ],
    ],
    'Color' => [
      'type' => 'color_field_type',
      'dependencies' => [
        'required' => [
          'module' => 'color_field',
        ],
      ],
    ],
    'File' => [
      'type' => 'file',
    ],
    'Layout canvas' => [
      'type' => 'cohesion_entity_reference_revisions',
      'dependencies' => [
          'required' => [
              'module' => 'cohesion_elements',
          ],
      ],
    ],
  ];
  const ENTITY_TYPE_MAPPING = [
    'Vocabulary' => [
      'entity_type_id' => 'taxonomy_vocabulary',
      'entity_type' => 'taxonomy_term',
    ],
    'Content type' => [
      'entity_type_id' => 'node_type',
      'entity_type' => 'node',
    ],
    'Paragraph type' => [
      'entity_type_id' => 'paragraphs_type',
      'entity_type' => 'paragraph',
    ],
    'Media type' => [
      'entity_type_id' => 'media_type',
      'entity_type' => 'media',
    ],
  ];

}
