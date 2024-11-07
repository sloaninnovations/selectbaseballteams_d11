<?php

declare(strict_types=1);

namespace Drupal\mail_theme_test\MailTheme;

use Drupal\Core\MailTheme\MailThemeNegotiatorInterface;

/**
 * Just forces the 'test_theme' theme for theme_test_trigger mail.
 */
class CustomThemeNegotiator implements MailThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(string $emailId): bool {
    return $emailId === 'theme_test_trigger';
  }

  /**
   * {@inheritdoc}
   */
  public function determineMailTheme(string $emailId): ?string {
    return 'test_theme';
  }

}
