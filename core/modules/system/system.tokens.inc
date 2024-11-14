<?php

/**
 * @file
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'site' and 'date' tokens.
 */

use Drupal\Core\Url;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function system_token_info() {
  $types['site'] = [
    'name' => t("Site information"),
    'description' => t("Tokens for site-wide settings and other global information."),
  ];
  $types['site-logo'] = [
    'name' => t('Site logo'),
    'description' => t('Tokens related to the site logo.'),
    'needs-data' => 'site',
  ];
  $types['site-logo-properties'] = [
    'name' => t('Site logo properties'),
    'description' => t('Tokens for site logo properties.'),
    'needs-data' => 'site-logo',
  ];
  $types['date'] = [
    'name' => t("Dates"),
    'description' => t("Tokens related to times and dates."),
  ];

  // Site-wide global tokens.
  $site['name'] = [
    'name' => t("Name"),
    'description' => t("The name of the site."),
  ];
  $site['slogan'] = [
    'name' => t("Slogan"),
    'description' => t("The slogan of the site."),
  ];
  $site['mail'] = [
    'name' => t("Email"),
    'description' => t("The administrative email address for the site."),
  ];
  $site['base-url'] = [
    'name' => t("Base URL"),
    'description' => t("The base URL of the site, currently: @base_url",
      [
        '@base_url' => \Drupal::service('router.request_context')->getCompleteBaseUrl(),
      ]),
  ];
  $site['base-path'] = [
    'name' => t("Base path"),
    'description' => t("The base path of the site, currently: @base_path", ['@base_path' => \Drupal::request()->getBasePath()]),
  ];
  $site['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the site's front page with the language prefix, if it exists."),
  ];
  $site['url-brief'] = [
    'name' => t("URL (brief)"),
    'description' => t("The URL of the site's front page without the protocol."),
  ];
  $site['login-url'] = [
    'name' => t("Login page"),
    'description' => t("The URL of the site's login page."),
  ];

  // The [site:logo] token renders the active theme logo. Chained tokens can be
  // used for rendering specific theme logos or logo properties (URLs) instead.
  $site['logo'] = [
    'name' => t('Logo'),
    'description' => t('The logo of the active theme. Note that the theme may be different when this is used in the administrative interface.'),
    'type' => 'site-logo',
  ];

  // Tokens for a specific theme logo.
  $site_logo['active-theme'] = [
    'name' => t('Active theme'),
    'description' => t('The logo of the active theme. Note that the theme may be different when this is used in the administrative interface.'),
    'type' => 'site-logo-properties',
  ];
  $site_logo['default-theme'] = [
    'name' => t('Default theme'),
    'description' => t('The logo that is configured for the default theme.'),
    'type' => 'site-logo-properties',
  ];
  // Obtain a list of installed themes and make tokens from them.
  $themes = \Drupal::service('theme_handler')->listInfo();
  foreach ($themes as $theme => $info) {
    if (empty($info->info['hidden'])) {
      $site_logo['theme-' . $theme] = [
        'name' => t('@theme', ['@theme' => $info->info['name']]),
        'description' => t('The logo that is configured for the %theme theme.', [
          '%theme' => $info->info['name'],
        ]),
        'type' => 'site-logo-properties',
      ];
    }
  }

  // Tokens for individual properties for logos.
  $site_logo_properties['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the logo.'),
  ];

  /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
  $date_formatter = \Drupal::service('date.formatter');

  // Date related tokens.
  $request_time = \Drupal::time()->getRequestTime();
  $date['short'] = [
    'name' => t("Short format"),
    'description' => t("The current date in 'short' format. (%date)", ['%date' => $date_formatter->format($request_time, 'short')]),
  ];
  $date['medium'] = [
    'name' => t("Medium format"),
    'description' => t("The current date in 'medium' format. (%date)", ['%date' => $date_formatter->format($request_time, 'medium')]),
  ];
  $date['long'] = [
    'name' => t("Long format"),
    'description' => t("The current date in 'long' format. (%date)", ['%date' => $date_formatter->format($request_time, 'long')]),
  ];
  $date['custom'] = [
    'name' => t("Custom format"),
    'description' => t('The current date in a custom format. See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters">the PHP documentation</a> for details.'),
  ];
  $date['since'] = [
    'name' => t("Time-since"),
    'description' => t("The current date in 'time-since' format. (%date)", ['%date' => $date_formatter->formatTimeDiffSince($request_time - 360)]),
  ];
  $date['raw'] = [
    'name' => t("Raw timestamp"),
    'description' => t("The current date in UNIX timestamp format (%date)", ['%date' => $request_time]),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'site' => $site,
      'site-logo' => $site_logo,
      'site-logo-properties' => $site_logo_properties,
      'date' => $date,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function system_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $site_name = $config->get('name');
          $replacements[$original] = $site_name;
          break;

        case 'slogan':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $slogan = $config->get('slogan');
          $build = [
            '#markup' => $slogan,
          ];
          // @todo Fix in https://www.drupal.org/node/2577827
          $replacements[$original] = \Drupal::service('renderer')->renderInIsolation($build);
          break;

        case 'mail':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $replacements[$original] = $config->get('mail');
          break;

        case 'base-url':
          $bubbleable_metadata->addCacheContexts(['url.site']);
          $replacements[$original] = \Drupal::service('router.request_context')->getCompleteBaseUrl();
          break;

        case 'base-path':
          $bubbleable_metadata->addCacheContexts(['url.site']);
          $replacements[$original] = \Drupal::request()->getBasePath();
          break;

        case 'url':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = Url::fromRoute('<front>', [], $url_options)->toString(TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = $result->getGeneratedUrl();
          break;

        case 'url-brief':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = Url::fromRoute('<front>', [], $url_options)->toString(TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = preg_replace(['!^https?://!', '!/$!'], '', $result->getGeneratedUrl());
          break;

        case 'login-url':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = Url::fromRoute('user.page', [], $url_options)->toString(TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = $result->getGeneratedUrl();
          break;

        case 'logo':
          $replacements[$original] = _system_tokens_get_logo_rendered('active-theme', $bubbleable_metadata);
          break;
      }
    }

    // Detect chained tokens ([site:logo:?]).
    if ($logo_tokens = $token_service->findWithPrefix($tokens, 'logo')) {
      $replacements += $token_service->generate('site-logo', $logo_tokens, [], $options, $bubbleable_metadata);
    }
  }
  elseif ($type == 'site-logo') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'active-theme':
        case 'default-theme':
          $replacements[$original] = _system_tokens_get_logo_rendered($name, $bubbleable_metadata);
          break;
      }
    }

    if ($logo_tokens = $token_service->findWithPrefix($tokens, 'active-theme')) {
      $replacements += $token_service->generate('site-logo-properties', $logo_tokens, ['theme' => 'active-theme'], $options, $bubbleable_metadata);
    }
    if ($logo_tokens = $token_service->findWithPrefix($tokens, 'default-theme')) {
      $replacements += $token_service->generate('site-logo-properties', $logo_tokens, ['theme' => 'default-theme'], $options, $bubbleable_metadata);
    }

    // List installed themes.
    $themes = \Drupal::service('theme_handler')->listInfo();
    $installed_themes = array_keys($themes);

    // Detect direct tokens ([site:logo:theme-olivero]).
    foreach ($tokens as $name => $original) {
      // Strip 'theme-' to get the theme name, but do not strip 'active-theme'.
      $name = str_starts_with($name, 'theme-') ? substr($name, 6) : $name;
      if (in_array($name, $installed_themes)) {
        $replacements[$original] = _system_tokens_get_logo_rendered($name, $bubbleable_metadata);
      }
    }

    // Detect chained tokens ([site:logo:theme-olivero:?]).
    foreach ($installed_themes as $installed_theme) {
      if ($created_tokens = $token_service->findWithPrefix($tokens, 'theme-' . $installed_theme)) {
        $replacements += $token_service->generate('site-logo-properties', $created_tokens, ['theme' => $installed_theme], $options, $bubbleable_metadata);
      }
    }
  }
  elseif ($type == 'site-logo-properties' && !empty($data['theme'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'url':
          $logo_path = _system_tokens_get_logo_path($data['theme'], $bubbleable_metadata);
          $replacements[$original] = Url::fromUserInput($logo_path, ['absolute' => TRUE])->toString();
          break;
      }
    }
  }
  elseif ($type == 'date') {
    if (empty($data['date'])) {
      $date = \Drupal::time()->getRequestTime();
      // We depend on the current request time, so the tokens are not cacheable
      // at all.
      $bubbleable_metadata->setCacheMaxAge(0);
    }
    else {
      $date = $data['date'];
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'short':
        case 'medium':
        case 'long':
          $date_format = DateFormat::load($name);
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($date, $name, '', NULL, $langcode);
          break;

        case 'since':
          $replacements[$original] = \Drupal::service('date.formatter')->formatTimeDiffSince($date, ['langcode' => $langcode]);
          $bubbleable_metadata->setCacheMaxAge(0);
          break;

        case 'raw':
          $replacements[$original] = $date;
          break;
      }
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'custom')) {
      foreach ($created_tokens as $name => $original) {
        $replacements[$original] = \Drupal::service('date.formatter')->format($date, 'custom', $name, NULL, $langcode);
      }
    }
  }

  return $replacements;
}

/**
 * Returns the path of the logo for the given theme.
 *
 * @param string $theme
 *   The theme to get the logo path for.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The bubbleable metadata to alter in order to cache the token.
 *
 * @return string|null
 *   The path to the logo, NULL if logo.url does not exist for the given theme.
 *
 * @see _system_tokens_get_logo_path()
 */
function _system_tokens_get_logo_path(string $theme, BubbleableMetadata &$bubbleable_metadata) {
  if ($theme === 'default-theme') {
    // Add the cache dependency.
    $system_theme_config = \Drupal::config('system.theme');
    $theme = $system_theme_config->get('default');
    $bubbleable_metadata->addCacheableDependency($system_theme_config);
  }
  elseif ($theme === 'active-theme') {
    $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    // Needs caching per theme, because it can change.
    $bubbleable_metadata->addCacheContexts(['theme']);
  }

  // Because theme_get_setting() can return either the global theme logo or a
  // theme specific logo, this token depends on both configurations.
  $global_theme_config = \Drupal::config('system.theme.global');
  $theme_config = \Drupal::config($theme . '.settings');
  $bubbleable_metadata->addCacheableDependency($global_theme_config);
  $bubbleable_metadata->addCacheableDependency($theme_config);

  return theme_get_setting('logo.url', $theme);
}

/**
 * Returns an <img> tag for the logo of the given theme.
 *
 *
 * @param string $theme
 *   The theme to get the logo path for.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   The bubbleable metadata to alter in order to cache the token.
 *
 * @return \Drupal\Component\Render\MarkupInterface
 *   An HTML <img> tag for the logo.
 *
 * @see _system_tokens_get_logo_path()
 */
function _system_tokens_get_logo_rendered(string $theme, BubbleableMetadata &$bubbleable_metadata) {
  $logo_path = _system_tokens_get_logo_path($theme, $bubbleable_metadata);
  $build = [
    '#theme' => 'image',
    '#uri' => $logo_path,
    '#alt' => t('The site logo'),
  ];
  return \Drupal::service('renderer')->renderPlain($build);
}
