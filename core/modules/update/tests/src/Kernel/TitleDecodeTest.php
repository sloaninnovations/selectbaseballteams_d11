<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

/**
 * Test the values set in update_calculate_project_data().
 *
 * @group update
 */
class TitleDecodeTest extends KernelTestBase {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'update', 'aaa_update_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The Update module's default configuration must be installed for our
    // fake release metadata to be fetched.
    $this->installConfig('update');
    $this->installConfig('aaa_update_test');

  }

  /**
   * Sets the release metadata file to use when fetching available updates.
   *
   * @param string $file
   *   The path of the XML metadata file to use.
   */
  protected function setReleaseMetadata(string $file): void {
    $metadata = Utils::tryFopen($file, 'r');
    $response = new Response(200, [], Utils::streamFor($metadata));
    $handler = new MockHandler([$response]);
    $this->client = new Client([
      'handler' => HandlerStack::create($handler),
    ]);
    $this->container->set('http_client', $this->client);
  }

  /**
   * Tests the project_status of the project.
   */
  public function testTitleDecode(): void {
    $fixture = '/../../fixtures/release-history/aaa_update_test.title-test.xml';
    update_storage_clear();

    $this->setReleaseMetadata(__DIR__ . $fixture);
    $available = update_get_available(TRUE);
    $this->assertEquals('CCC Update & test', $available['drupal']['title']);
  }

}
