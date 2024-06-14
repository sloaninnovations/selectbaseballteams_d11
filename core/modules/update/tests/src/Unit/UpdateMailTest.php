<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

/**
 * Tests text of update email.
 *
 * @covers \update_mail
 *
 * @group update
 */
class UpdateMailTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The mocked current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Mocked language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mocked URL generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    include_once __DIR__ . '/../../../update.module';

    // Initialize the container.
    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($this->container);

    // Get needed mocks.
    $this->currentUser = $this->createMock('\Drupal\Core\Session\AccountProxy');
    $this->languageManager = $this->createMock('Drupal\language\ConfigurableLanguageManagerInterface');
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactory');
    $this->urlGenerator = $this->createMock('\Drupal\Core\Render\MetadataBubblingUrlGenerator');
  }

  /**
   * Test the subject and body of update text.
   *
   * @dataProvider providerTestUpdateEmail
   */
  public function testUpdateEmail(string $notification_threshold, array $params, bool $authorized, string $expected_subject, array $expected_body): void {
    $site_name = 'Test site';
    $expected_subject .= $site_name;
    $langcode = 'en';
    $available_updates_url = 'https://example.com/admin/reports/updates';
    $update_settings_url = 'https://example.com/admin/reports/updates/settings';

    // Initialize update_mail input parameters.
    $key = NULL;
    $message = [
      'langcode' => $langcode,
      'subject' => '',
      'message' => '',
      'body' => [],
    ];

    // Language manager just returns the language.
    $this->languageManager
      ->expects($this->once())
      ->method('getLanguage')
      ->willReturn($langcode);

    // Create three config entities.
    $config_site_name = $this->createMock('Drupal\Core\Config\Config');
    $config_site_name
      ->expects($this->any())
      ->method('get')
      ->with('name')
      ->willReturn($site_name);
    $config_notification = $this->createMock('Drupal\Core\Config\Config');
    $config_notification
      ->expects($this->once())
      ->method('get')
      ->with('notification.threshold')
      ->willReturn($notification_threshold);

    $this->configFactory
      ->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['system.site', $config_site_name],
        ['update.settings', $config_notification],
      ]);

    // The calls to generateFromRoute differ if authorized.
    if ($authorized) {
      $this->currentUser
        ->expects($this->once())
        ->method('hasPermission')
        ->with('administer software updates')
        ->willReturn(TRUE);
    }

    $this->urlGenerator
      ->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnMap([
        ['update.status', [], ['absolute' => TRUE, 'language' => $langcode], FALSE, $update_settings_url],
        ['update.settings', [], ['absolute' => TRUE], FALSE, $available_updates_url],
        ['update.report_update', [], ['absolute' => TRUE, 'language' => $langcode], FALSE, $available_updates_url],
        ['update.status', [], [], FALSE, $update_settings_url],
      ]);

    // Set the container.
    $this->container->set('language_manager', $this->languageManager);
    $this->container->set('url_generator', $this->urlGenerator);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('current_user', $this->currentUser);
    \Drupal::setContainer($this->container);

    // Generate the email message.
    update_mail($key, $message, $params);

    // Confirm the subject.
    is_string($message['subject']) ? $this->assertSame($expected_subject, $message['subject']) : $this->assertSame($expected_subject, $message['subject']->render());

    // Confirm each part of the body.
    for ($i = 0; $i < count($expected_body); $i++) {
      is_string($message['body'][$i]) ? $this->assertSame($expected_body[$i], $message['body'][$i]) : $this->assertSame($expected_body[$i], $message['body'][$i]->render());
    }
  }

  /**
   * Provides data for ::testUpdateEmail.
   *
   * @return array
   *   - The value of the update setting 'notification.threshold'.
   *   - An array of parameters for update_mail.
   *   - TRUE if the user is authorized.
   *   - The subject string without the trailing site name.
   *   - An array of message body strings.
   */
  public static function providerTestUpdateEmail(): array {
    return [
      'all' => [
        'all',
        [],
        FALSE,
        "New release(s) available for ",
      [
        "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
        'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.',
      ],
      ],
      'security' => [
        'security',
        [],
        FALSE,
        "New release(s) available for ",
        [
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::NOT_SECURE,
          'contrib' => NULL,
        ],
        FALSE,
        'Security release(s) available for ',
        [
          "There is a security update available for your version of Drupal. To ensure the security of your server, you should update immediately!",
          '',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'authorize' => [
        'all',
        [],
        TRUE,
        "New release(s) available for ",
        [
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "You can automatically download your missing updates using the Update manager:\nhttps://example.com/admin/reports/updates",
          'Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.',
        ],
      ],
      'contrib not secure' => [
        'security',
        [
          'core' => UpdateManagerInterface::CURRENT,
          'contrib' => UpdateManagerInterface::NOT_SECURE,
        ],
        FALSE,
        'Security release(s) available for ',
        [
          '',
          "There are security updates available for one or more of your modules or themes. To ensure the security of your server, you should update immediately!",
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails only when security updates are available. To get notified for any available updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'all current' => [
        'all',
        [
          'core' => UpdateManagerInterface::CURRENT,
          'contrib' => UpdateManagerInterface::CURRENT,
        ],
        FALSE,
        "New release(s) available for ",
        [
          "",
          "",
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'all not current' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        FALSE,
        "New release(s) available for ",
        [
          "There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.",
          "There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.",
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'core only fetcher failed' => [
        'all',
        [
          'core' => UpdateFetcherInterface::UNKNOWN,
        ],
        FALSE,
        "Failed to get release information for ",
        [
          'There was a problem checking <a href="https://example.com/admin/reports/updates/settings">available updates</a> for Drupal.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'core fetch fail' => [
        'all',
        [
          'core' => UpdateFetcherInterface::UNKNOWN,
          'contrib' => UpdateManagerInterface::NOT_CURRENT,
        ],
        FALSE,
        "New release(s) available for ",
        [
          'There was a problem checking <a href="https://example.com/admin/reports/updates/settings">available updates</a> for Drupal.',
          'There are updates available for one or more of your modules or themes. To ensure the proper functioning of your site, you should update as soon as possible.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'contrib, fetch fail' => [
        'all',
        [
          'core' => UpdateManagerInterface::NOT_CURRENT,
          'contrib' => UpdateFetcherInterface::NOT_FETCHED,
        ],
        FALSE,
        "New release(s) available for ",
        [
          "There are updates available for your version of Drupal. To ensure the proper functioning of your site, you should update as soon as possible.",
          'There was a problem checking <a href="https://example.com/admin/reports/updates/settings">available updates</a> for your modules or themes.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
      'all fetch fail' => [
        'all',
        [
          'core' => UpdateFetcherInterface::NOT_FETCHED,
          'contrib' => UpdateFetcherInterface::NOT_FETCHED,
        ],
        FALSE,
        "Failed to get release information for ",
        [
          'There was a problem checking <a href="https://example.com/admin/reports/updates/settings">available updates</a> for Drupal.',
          'There was a problem checking <a href="https://example.com/admin/reports/updates/settings">available updates</a> for your modules or themes.',
          "See the available updates page for more information:\nhttps://example.com/admin/reports/updates/settings",
          "Your site is currently configured to send these emails when any updates are available. To get notified only for security updates, https://example.com/admin/reports/updates.",
        ],
      ],
    ];
  }

}
