<?php

declare(strict_types=1);
namespace Drupal\Tests\system\Kernel\FileTransfer;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests recursive file copy operations with the file transfer jail.
 *
 * @group FileTransfer
 */
class FileTransferTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'system',
    'user',
  ];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The connection.
   *
   * @var \Drupal\Tests\system\Kernel\FileTransfer\TestFileTransfer
   */
  protected TestFileTransfer $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');

    $this->fileSystem = $this->container->get('file_system');
    $this->connection = TestFileTransfer::factory($this->root, []);
    $this->connection->connect();
    $this->createFakeModule();
  }

  /**
   * Creates a module directory and file structure in temporary://.
   */
  public function createFakeModule(): void {
    $file_names = [
      'fake.module',
      'fake.info.yml',
      'theme\fake.html.twig',
      'inc\fake.inc',
    ];

    foreach ($file_names as $file_name) {
      $filepath = $this->fileSystem->createFilename($file_name, 'fake');
      $file_uri = 'temporary://' . $filepath;
      $directory_uri = 'temporary://' . dirname($filepath);
      $this->fileSystem->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY);

      file_put_contents($file_uri, str_repeat('t', 10));
      $file = File::create(['uri' => $file_uri, 'filename' => $file_name]);
      $file->save();
      $this->assertFileExists($file_uri);
    }
  }

  /**
   * Tests copying directories in and outside the jail.
   *
   * Note that we're only testing the (absence of) a FileTransferException, not
   * tbe actual copying.
   */
  public function testJail(): void {
    // Copying to a directory inside the jail shouldn't throw an exception.
    $destination = $this->root . '/' . PublicStream::basePath();
    $this->connection->copyDirectory('temporary://fake', $destination);

    // Copying to a directory outside the jail is not allowed.
    $this->expectException(FileTransferException::class);
    $this->expectExceptionMessage('@directory is outside of the @jail');
    $this->connection->copyDirectory('temporary://fake', sys_get_temp_dir());
  }

}
