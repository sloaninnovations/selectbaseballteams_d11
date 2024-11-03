<?php

/**
 * @file
 * Provides views data for locale.module.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 */
function locale_views_data(): array {
  $data = [];
  $data['locales_source']['table']['group'] = t('Locale source');

  $data['locales_source']['table']['base'] = [
    'field' => 'lid',
    'title' => t('Locale source'),
    'help' => t('A source string for translation, in English or the default site language.'),
  ];

  $data['locales_source']['lid'] = [
    'title' => t('LID'),
    'help' => t('The ID of the source string.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'numeric',
      'numeric' => TRUE,
      'validate type' => 'lid',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_source']['source'] = [
    'title' => t('Source'),
    'help' => t('The full original string.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  $data['locales_source']['context'] = [
    'title' => t('Context'),
    'help' => t('The context this string applies to.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_source']['version'] = [
    'title' => t('Version'),
    'help' => t('The release version of the project.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'locale_version',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_source']['edit_lid'] = [
    'field' => [
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the translations.'),
      'id' => 'locale_link_edit',
    ],
  ];

  $data['locales_target']['table']['group'] = t('Locale target');

  $data['locales_target']['table']['join'] = [
    'locales_source' => [
      'left_field' => 'lid',
      'field' => 'lid',
    ],
  ];

  $data['locales_target']['translation'] = [
    'title' => t('Translation'),
    'help' => t('The full translation string.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_target']['language'] = [
    'title' => t('Language'),
    'help' => t('The language this translation is in.'),
    'field' => [
      'id' => 'language',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'language',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_target']['customized'] = [
    'title' => t('Customized'),
    'help' => t('Boolean indicating whether the translation is custom to this site.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['locales_location']['table']['group'] = t('Locale location');

  $data['locales_location']['table']['join'] = [
    'locales_source' => [
      'left_field' => 'lid',
      'field' => 'lid',
    ],
    'locales_target' => [
      'left_field' => 'lid',
      'field' => 'lid',
    ],
  ];

  $data['locales_location']['type'] = [
    'title' => t('Type'),
    'help' => t('The location type (file, config, path, etc).'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_location']['name'] = [
    'title' => t('Name'),
    'help' => t('Type dependent location information (file name, path, etc).'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locales_location']['version'] = [
    'title' => t('Version'),
    'help' => t('Version of Drupal where the location was found.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'locale_version',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_file']['table']['group'] = t('Locale group');

  $data['locale_file']['langcode'] = [
    'title' => t('Langcode'),
    'help' => t('The language the file is for.'),
    'field' => [
      'id' => 'language',
    ],
    'filter' => [
      'id' => 'language',
    ],
    'argument' => [
      'id' => 'language',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_file']['filename'] = [
    'title' => t('Langcode'),
    'help' => t('Filename for importing the file.'),
    'field' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_file']['uri'] = [
    'title' => t('URI'),
    'help' => t('File system path for importing the file.'),
    'field' => [
      'id' => 'url',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_file']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Unix timestamp of the file itself from the point when it was last imported.'),
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_project']['table']['group'] = t('Locale project');

  $data['locale_project']['table']['base'] = [
    'field' => 'name',
    'title' => t('Locale project'),
  ];

  $data['locale_project']['name'] = [
    'title' => t('Name'),
    'help' => t('The short name of the project'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_project']['project_type'] = [
    'title' => t('Type'),
    'help' => t('The project type, so either core, modules or theme.'),
    'field' => [
      'id' => 'machine_name',
      'options callback' => 'locale_views_project_types',
    ],
    'filter' => [
      'id' => 'in_operator',
      'options callback' => 'locale_views_project_types',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_project']['core'] = [
    'title' => t('Core'),
    'help' => t('The version of Drupal core that this string is for.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_project']['server_pattern'] = [
    'title' => t('Server pattern'),
    'help' => t('Pattern of path and name of the gettext file at the translation server.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['locale_project']['status'] = [
    'title' => t('Status'),
    'help' => t('The update status of the project.'),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}

/**
 * Returns human readable project types.
 *
 * @return array
 *   An array keyed by project_type with human names for these types.
 */
function locale_views_project_types(): array {
  return [
    'core' => t('Drupal core'),
    'module' => t('Module'),
    'theme' => t('Theme'),
  ];
}
