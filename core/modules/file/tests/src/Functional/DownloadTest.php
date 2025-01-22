<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Core\Database\Database;
use Drupal\file_test\FileTestHelper;

/**
 * Tests for download/file transfer functions.
 *
 * @group file
 */
class DownloadTest extends FileManagedTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test currently frequently causes the SQLite database to lock, so
    // skip the test on SQLite until the issue can be resolved.
    // @todo Fix root cause and re-enable in
    //   https://www.drupal.org/project/drupal/issues/3311587
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped('Test frequently causes a locked database on SQLite');
    }

    $this->fileUrlGenerator = $this->container->get('file_url_generator');
    // Clear out any hook calls.
    FileTestHelper::reset();
  }

  /**
   * Tests the private file transfer system.
   */
  public function testPrivateFileTransferWithoutPageCache(): void {
    $this->doPrivateFileTransferTest();
  }

  /**
   * Tests the private file transfer system.
   */
  protected function doPrivateFileTransferTest(): void {
    // Set file downloads to private so handler functions get called.

    // Create a file.
    $contents = $this->randomMachineName(8);
    $file = $this->createFile($contents . '.txt', $contents, 'private');
    // Created private files without usage are by default not accessible
    // for a user different from the owner, but createFile always uses uid 1
    // as the owner of the files. Therefore make it permanent to allow access
    // if a module allows it.
    $file->setPermanent();
    $file->save();

    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

    // Set file_test access header to allow the download.
    FileTestHelper::reset();
    FileTestHelper::setReturn('download', ['x-foo' => 'Bar']);
    $this->drupalGet($url);
    // Verify that header is set by file_test module on private download.
    $this->assertSession()->responseHeaderEquals('x-foo', 'Bar');
    // Verify that page cache is disabled on private file download.
    $this->assertSession()->responseHeaderDoesNotExist('x-drupal-cache');
    $this->assertSession()->statusCodeEquals(200);
    // Ensure hook_file_download is fired correctly.
    $this->assertEquals($file->getFileUri(), \Drupal::keyValue('file_test')->get('results')['download'][0][0]);

    // Test that the file transferred correctly.
    $this->assertSame($contents, $this->getSession()->getPage()->getContent(), 'Contents of the file are correct.');
    $http_client = $this->getHttpClient();

    // Try non-existent file.
    FileTestHelper::reset();
    $not_found_url = $this->fileUrlGenerator->generateAbsoluteString('private://' . $this->randomMachineName() . '.txt');
    $response = $http_client->head($not_found_url, ['http_errors' => FALSE]);
    $this->assertSame(404, $response->getStatusCode(), 'Correctly returned 404 response for a non-existent file.');
    // Assert that hook_file_download is not called.
    $this->assertEquals([], \Drupal::keyValue('file_test')->get('results')['download']);

    // Having tried a non-existent file, try the original file again to ensure
    // it's returned instead of a 404 response.
    // Set file_test access header to allow the download.
    FileTestHelper::reset();
    FileTestHelper::setReturn('download', ['x-foo' => 'Bar']);
    $this->drupalGet($url);
    // Verify that header is set by file_test module on private download.
    $this->assertSession()->responseHeaderEquals('x-foo', 'Bar');
    // Verify that page cache is disabled on private file download.
    $this->assertSession()->responseHeaderDoesNotExist('x-drupal-cache');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file transferred correctly.
    $this->assertSame($contents, $this->getSession()->getPage()->getContent(), 'Contents of the file are correct.');

    // Deny access to all downloads via a -1 header.
    FileTestHelper::setReturn('download', -1);
    $response = $http_client->head($url, ['http_errors' => FALSE]);
    $this->assertSame(403, $response->getStatusCode(), 'Correctly denied access to a file when file_test sets the header to -1.');

    // Try requesting the private file URL without a file specified.
    FileTestHelper::reset();
    $this->drupalGet('/system/files');
    $this->assertSession()->statusCodeEquals(404);
    // Assert that hook_file_download is not called.
    $this->assertEquals([], \Drupal::keyValue('file_test')->get('results')['download']);
  }

}
