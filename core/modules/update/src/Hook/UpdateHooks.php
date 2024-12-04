<?php

namespace Drupal\update\Hook;

use Drupal\update\UpdateManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for update.
 */
class UpdateHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.update':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Update Manager module periodically checks for new versions of your site\'s software (including contributed modules and themes), and alerts administrators to available updates. The Update Manager system is also used by some other modules to manage updates and downloads; for example, the Interface Translation module uses the Update Manager to download translations from the localization server. Note that whenever the Update Manager system is used, anonymous usage statistics are sent to Drupal.org. If desired, you may uninstall the Update Manager module from the <a href=":modules">Extend page</a>; if you do so, functionality that depends on the Update Manager system will not work. For more information, see the <a href=":update">online documentation for the Update Manager module</a>.', [
          ':update' => 'https://www.drupal.org/documentation/modules/update',
          ':modules' => Url::fromRoute('system.modules_list')->toString(),
        ]) . '</p>';
        // Only explain the Update manager if it has not been uninstalled.
        if (_update_manager_access()) {
          $output .= '<p>' . t('The Update Manager also allows administrators to add and update modules and themes through the administration interface.') . '</p>';
        }
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Checking for available updates') . '</dt>';
        $output .= '<dd>' . t('The <a href=":update-report">Available updates report</a> displays core, contributed modules, and themes for which there are new releases available for download. On the report page, you can also check manually for updates. You can configure the frequency of update checks, which are performed during cron runs, and whether notifications are sent on the <a href=":update-settings">Update Manager settings page</a>.', [
          ':update-report' => Url::fromRoute('update.status')->toString(),
          ':update-settings' => Url::fromRoute('update.settings')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'update.status':
        return '<p>' . t('Here you can find information about available updates for your installed modules and themes. Note that each module or theme is part of a "project", which may or may not have the same name, and might include multiple modules or themes within it.') . '</p>';

      case 'system.modules_list':
        return '<p>' . t('Regularly review <a href=":updates">available updates</a> and update as required to maintain a secure and current site. Always run the <a href=":update-php">update script</a> each time you update software.', [
          ':update-php' => Url::fromRoute('system.db_update')->toString(),
          ':updates' => Url::fromRoute('update.status')->toString(),
        ]) . '</p>';
    }
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(): void {
    /** @var \Drupal\Core\Routing\AdminContext $admin_context */
    $admin_context = \Drupal::service('router.admin_context');
    $route_match = \Drupal::routeMatch();
    if ($admin_context->isAdminRoute($route_match->getRouteObject()) && \Drupal::currentUser()->hasPermission('view update notifications')) {
      $route_name = \Drupal::routeMatch()->getRouteName();
      switch ($route_name) {
        // These pages don't need additional nagging.
        case 'update.status':
        case 'update.settings':
        case 'system.status':
        case 'system.theme_install':
        case 'system.batch_page.html':
          return;

        // If we are on the appearance or modules list, display a detailed report
        // of the update status.
        case 'system.themes_page':
        case 'system.modules_list':
          $verbose = TRUE;
          break;
      }
      \Drupal::moduleHandler()->loadInclude('update', 'install');
      $status = update_requirements('runtime');
      foreach (['core', 'contrib'] as $report_type) {
        $type = 'update_' . $report_type;
        // hook_requirements() supports render arrays therefore we need to render
        // them before using
        // \Drupal\Core\Messenger\MessengerInterface::addStatus().
        if (isset($status[$type]['description']) && is_array($status[$type]['description'])) {
          $status[$type]['description'] = \Drupal::service('renderer')->renderInIsolation($status[$type]['description']);
        }
        if (!empty($verbose)) {
          if (isset($status[$type]['severity'])) {
            if ($status[$type]['severity'] == REQUIREMENT_ERROR) {
              \Drupal::messenger()->addError($status[$type]['description']);
            }
            elseif ($status[$type]['severity'] == REQUIREMENT_WARNING) {
              \Drupal::messenger()->addWarning($status[$type]['description']);
            }
          }
        }
        else {
          if (isset($status[$type]) && isset($status[$type]['reason']) && $status[$type]['reason'] === UpdateManagerInterface::NOT_SECURE) {
            \Drupal::messenger()->addError($status[$type]['description']);
          }
        }
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'update_last_check' => [
        'variables' => [
          'last' => 0,
        ],
      ],
      'update_report' => [
        'variables' => [
          'data' => NULL,
        ],
        'file' => 'update.report.inc',
      ],
      'update_project_status' => [
        'variables' => [
          'project' => [],
        ],
        'file' => 'update.report.inc',
      ],
          // We are using template instead of '#type' => 'table' here to keep markup
          // out of preprocess and allow for easier changes to markup.
      'update_version' => [
        'variables' => [
          'version' => NULL,
          'title' => NULL,
          'attributes' => [],
        ],
        'file' => 'update.report.inc',
      ],
      'update_fetch_error_message' => [
        'file' => 'update.report.inc',
        'render element' => 'element',
        'variables' => [
          'error_message' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $update_config = \Drupal::config('update.settings');
    $frequency = $update_config->get('check.interval_days');
    $interval = 60 * 60 * 24 * $frequency;
    $last_check = \Drupal::state()->get('update.last_check', 0);
    $request_time = \Drupal::time()->getRequestTime();
    if ($request_time - $last_check > $interval) {
      // If the configured update interval has elapsed, we want to invalidate
      // the data for all projects, attempt to re-fetch, and trigger any
      // configured notifications about the new status.
      update_refresh();
      update_fetch_data();
    }
    else {
      // Otherwise, see if any individual projects are now stale or still
      // missing data, and if so, try to fetch the data.
      update_get_available(TRUE);
    }
    $last_email_notice = \Drupal::state()->get('update.last_email_notification', 0);
    if ($request_time - $last_email_notice > $interval) {
      // If configured time between notifications elapsed, send email about
      // updates possibly available.
      \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.fetch');
      _update_cron_notify();
    }
    // Clear garbage from disk.
    update_clear_update_disk_cache();
  }

  /**
   * Implements hook_themes_installed().
   *
   * If themes are installed, we invalidate the information of available updates.
   */
  #[Hook('themes_installed')]
  public function themesInstalled($themes) {
    // Clear all update module data.
    update_storage_clear();
  }

  /**
   * Implements hook_themes_uninstalled().
   *
   * If themes are uninstalled, we invalidate the information of available updates.
   */
  #[Hook('themes_uninstalled')]
  public function themesUninstalled($themes) {
    // Clear all update module data.
    update_storage_clear();
  }

  /**
   * Implements hook_modules_installed().
   *
   * If modules are installed, we invalidate the information of available updates.
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules) {
    // Clear all update module data.
    update_storage_clear();
  }

  /**
   * Implements hook_modules_uninstalled().
   *
   * If modules are uninstalled, we invalidate the information of available updates.
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules) {
    // Clear all update module data.
    update_storage_clear();
  }

  /**
   * Implements hook_mail().
   *
   * Constructs the email notification message when the site is out of date.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   * @see _update_cron_notify()
   * @see _update_message_text()
   * @see \Drupal\update\UpdateManagerInterface
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    $langcode = $message['langcode'];
    $language = \Drupal::languageManager()->getLanguage($langcode);
    $message['subject'] .= t('New release(s) available for @site_name', ['@site_name' => \Drupal::config('system.site')->get('name')], ['langcode' => $langcode]);
    foreach ($params as $msg_type => $msg_reason) {
      $message['body'][] = _update_message_text($msg_type, $msg_reason, $langcode);
    }
    $message['body'][] = t('See the available updates page for more information:', [], ['langcode' => $langcode]) . "\n" . Url::fromRoute('update.status', [], ['absolute' => TRUE, 'language' => $language])->toString();
    if (_update_manager_access()) {
      $message['body'][] = t('You can automatically download your missing updates using the Update manager:', [], ['langcode' => $langcode]) . "\n" . Url::fromRoute('update.report_update', [], ['absolute' => TRUE, 'language' => $language])->toString();
    }
    $settings_url = Url::fromRoute('update.settings', [], ['absolute' => TRUE])->toString();
    if (\Drupal::config('update.settings')->get('notification.threshold') == 'all') {
      $message['body'][] = t('Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, @url.', ['@url' => $settings_url]);
    }
    else {
      $message['body'][] = t('Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, @url.', ['@url' => $settings_url]);
    }
  }

}
