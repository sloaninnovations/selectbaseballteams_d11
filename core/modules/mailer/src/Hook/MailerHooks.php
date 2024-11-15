<?php

namespace Drupal\mailer\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for mailer.
 */
class MailerHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) : ?string {
    switch ($route_name) {
      case 'help.page.mailer':
        $output = '';
        $output .= '<h3>' . t('About') . '</h3>';
        $output .= '<p>' . t('The Mailer module provides an experimental API to build and deliver email messages based on Symfony mailer component. For more information, see the <a href=":mailer">online documentation for the Mailer module</a>.', [
          ':mailer' => 'https://www.drupal.org/docs/core-modules-and-themes/experimental-extensions/experimental-modules/mailer',
        ]) . '</p>';
        return $output;

      default:
        return NULL;
    }
  }

}
