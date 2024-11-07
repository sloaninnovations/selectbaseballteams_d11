<?php

namespace Drupal\Core\MailTheme;

/**
 * Interface for mail theme manager implementations.
 *
 * Provides a simple helper method which switches the theme to a mail theme.
 * Doing so ensures that mails are always using the same templates, no matter
 * whether they were sent in response to a user action on the frontend or inside
 * an administrative area or even during a cron run.
 *
 * Use the following pattern when preparing or sending mails:
 *
 * $result = $this->mailThemeManager->executeInMailTheme($module . '_' . $key, function () {
 *   return $this->renderer->executeInRenderContext(new RenderContext(), function () {
 *     // Do stuff (e.g. replace tokens, render an entity, ...)
 *     return $result;
 *   });
 * });
 */
interface MailThemeManagerInterface {

  /**
   * Switches to the theme for the given email id and runs a callback.
   *
   * @template Result
   *
   * @param string $emailId
   *   The email id used to look up the theme.
   * @param callable(): Result $function
   *   The callback to be executed.
   *
   * @return Result
   */
  public function executeInMailTheme(string $emailId, callable $function): mixed;

}
