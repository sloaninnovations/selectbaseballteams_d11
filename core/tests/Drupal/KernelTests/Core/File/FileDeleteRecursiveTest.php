<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

/**
 * Tests the unmanaged file delete recursive function.
 *
 * @group File
 */
class FileDeleteRecursiveTest extends FileTestBase {

  /**
   * Delete a normal file.
   */
  public function testSingleFile(): void {
    // Create a file for testing
    $filepath = 'public://' . $this->randomMachineName();
    file_put_contents($filepath, '');

    // Delete the file.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($filepath), 'Function reported success.');
    $this->assertFileDoesNotExist($filepath);
  }

  /**
   * Try deleting an empty directory.
   */
  public function testEmptyDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting a directory with some files.
   */
  public function testDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $filepathA = $directory . '/A';
    $filepathB = $directory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Try deleting subdirectories with some files.
   */
  public function testSubDirectory(): void {
    // A directory to operate on.
    $directory = $this->createDirectory();
    $subdirectory = $this->createDirectory($directory . '/sub');
    $filepathA = $directory . '/A';
    $filepathB = $subdirectory . '/B';
    file_put_contents($filepathA, '');
    file_put_contents($filepathB, '');

    // Delete the directory.
    $this->assertTrue(\Drupal::service('file_system')->deleteRecursive($directory), 'Function reported success.');
    $this->assertFileDoesNotExist($filepathA);
    $this->assertFileDoesNotExist($filepathB);
    $this->assertDirectoryDoesNotExist($subdirectory);
    $this->assertDirectoryDoesNotExist($directory);
  }

  /**
   * Tests symlinks to directories do not result in unexpected deletions.
   */
  public function testSymlinks(): void {
    // Create files to link to.
    mkdir($this->siteDirectory . '/dir1');
    touch($this->siteDirectory . '/dir1/test.txt');

    // Create directory to be deleted.
    mkdir($this->siteDirectory . '/dir2');
    symlink(realpath($this->siteDirectory . '/dir1'), $this->siteDirectory . '/dir2/subdir');
    $this->assertFileExists($this->siteDirectory . '/dir2/subdir/test.txt');

    $this->container->get('file_system')->deleteRecursive($this->siteDirectory . '/dir2');
    $this->assertFileExists($this->siteDirectory . '/dir1/test.txt');
    $this->assertDirectoryDoesNotExist($this->siteDirectory . '/dir2');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->assertDirectoryExists($this->siteDirectory);
    parent::tearDown();

    // Ensure \Drupal\KernelTests\KernelTestBase::tearDown() has cleaned up the
    // file system.
    $this->assertDirectoryDoesNotExist($this->siteDirectory);
  }

}
