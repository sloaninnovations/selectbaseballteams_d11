<?php

declare(strict_types=1);

namespace Drupal\Core\MailTheme;

use Drupal\Core\Mail\MailTemplateId;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Default mail theme manager implementation.
 */
class MailThemeManager implements MailThemeManagerInterface {

  /**
   * Constructs a new mail theme manager.
   *
   * @param \Drupal\Core\MailTheme\MailThemeNegotiatorInterface $themeNegotiator
   *   The mail theme negotiator.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $themeInitialization
   *   The theme initialization service.
   */
  public function __construct(
    protected MailThemeNegotiatorInterface $themeNegotiator,
    protected ThemeManagerInterface $themeManager,
    protected ThemeInitializationInterface $themeInitialization,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function executeInMailTheme(MailTemplateId $templateId, callable $function): mixed {
    // 1. Negotiate the theme.
    $theme = $this->themeNegotiator->determineMailTheme($templateId);
    $previousTheme = NULL;
    if ($theme) {
      $previousTheme = $this->switchTheme($theme);
    }

    try {
      // 2. Perform action.
      $result = $function();
    }
    finally {
      // 3. Switch theme back.
      if ($previousTheme) {
        $this->switchTheme($previousTheme);
      }
    }

    return $result;
  }

  /**
   * Switch to the given theme if necessary.
   *
   * @param string $theme
   *   The new theme ID.
   *
   * @return string
   *   The previous theme ID.
   */
  protected function switchTheme(string $theme): string {
    $previousTheme = $this->themeManager->getActiveTheme();
    if ($previousTheme->getName() !== $theme) {
      $activeTheme = $this->themeInitialization->initTheme($theme);
      $this->themeManager->setActiveTheme($activeTheme);
    }

    return $previousTheme->getName();
  }

}
