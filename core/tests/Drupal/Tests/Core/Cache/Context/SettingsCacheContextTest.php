<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\SettingsCacheContext;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\SettingsCacheContext
 * @group Cache
 */
class SettingsCacheContextTest extends UnitTestCase {

  /**
   * @covers ::getContext
   * @dataProvider providerTestGetContext
   */
  public function testGetContext(array $settings, string $expected): void {
    $cache_context = new SettingsCacheContext();
    new Settings($settings + ['hash_salt' => 'foobar']);
    $this->assertSame($expected, $cache_context->getContext());
  }

  /**
   * Data provider for testGetContext().
   */
  public static function providerTestGetContext(): array {
    // cSpell:disable
    return [
      'hash salt foo' => [
        [
          'hash_salt' => 'foo',
        ],
        'xJha8kdJd-b8oJTIvow7StRnyqCvp66fxGUdzga9-sw',
      ],
      'hash salt bar' => [
        [
          'hash_salt' => 'bar',
        ],
        'nUzshkpnpT21HTFDMIvRtyx9YXKHwdezk_kV4gH2Oi0',
      ],
      'example settings rebuild_access' => [
        [
          'rebuild_access' => TRUE,
        ],
        '1uc5s2AozS_W0YfuoHDghu7TyMh92YosCFy5nVTtVdo',
      ],
      'example settings skip_permissions_hardening' => [
        [
          'skip_permissions_hardening' => TRUE,
        ],
        '1aPmhCzuNkX7zBXFObwxFqL2cMSlLDhoa4mkzxQf6wQ',
      ],
      'allow authorize operations' => [
        [
          'allow_authorize_operations' => FALSE,
        ],
        '2T-MBIQq5LerfNyeOmczTqE5rOn2CXJ2RSawsNrnTUQ',
      ],
    ];
    // cspell:enable
  }

}
