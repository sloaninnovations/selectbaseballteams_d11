<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Provides a base class for testing file uploads and hook invocations.
 */
abstract class FileManagedUnitTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test', 'file', 'system', 'field', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Clear out any hook calls.
    file_test_reset();

    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    // Make sure that a user with uid 1 exists, self::createFile() relies on
    // it.
    $user = User::create(['uid' => 1, 'name' => $this->randomMachineName()]);
    $user->enforceIsNew();
    $user->save();
    \Drupal::currentUser()->setAccount($user);
  }

  /**
   * Asserts that the specified file hooks were called only once.
   *
   * @param array $expected
   *   Array with string containing with the hook name, e.g. 'load', 'save',
   *   'insert', etc.
   */
  public function assertFileHooksCalled($expected) {
    \Drupal::state()->resetCache();

    // Determine which hooks were called.
    $actual = array_keys(array_filter(file_test_get_all_calls()));

    // Determine if there were any expected that were not called, or any
    // unexpected calls.
    $this->assertEqualsCanonicalizing($expected, $actual);
  }

  /**
   * Assert that a hook_file_* hook was called a certain number of times.
   *
   * @param string $hook
   *   String with the hook name, e.g. 'load', 'save', 'insert', etc.
   * @param int $expected_count
   *   Optional integer count.
   * @param string $message
   *   Optional translated string message.
   */
  public function assertFileHookCalled($hook, $expected_count = 1, $message = NULL) {
    $this->assertCount($expected_count, file_test_get_calls($hook), $message ?? "hook_file_$hook was called correctly.");
  }

  /**
   * Asserts that two files have the same values (except timestamp).
   *
   * @param \Drupal\file\FileInterface $before
   *   File object to compare.
   * @param \Drupal\file\FileInterface $after
   *   File object to compare.
   */
  public function assertFileUnchanged(FileInterface $before, FileInterface $after) {
    $this->assertEquals($before->id(), $after->id(), 'File id is the same');
    $this->assertEquals($before->getOwner()->id(), $after->getOwner()->id(), 'File owner is the same');
    $this->assertEquals($before->getFilename(), $after->getFilename(), 'File name is the same');
    $this->assertEquals($before->getFileUri(), $after->getFileUri(), 'File path is the same');
    $this->assertEquals($before->getMimeType(), $after->getMimeType(), 'File MIME type is the same');
    $this->assertEquals($before->getSize(), $after->getSize(), 'File size is the same');
    $this->assertEquals($before->isPermanent(), $after->isPermanent(), 'File status is the same');
  }

  /**
   * Asserts that two files are not the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  public function assertDifferentFile(FileInterface $file1, FileInterface $file2) {
    $this->assertNotEquals($file1->id(), $file2->id(), 'Files have different ids');
    $this->assertNotEquals($file1->getFileUri(), $file2->getFileUri(), 'Files have different paths');
  }

  /**
   * Asserts that two files are the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  public function assertSameFile(FileInterface $file1, FileInterface $file2) {
    $this->assertEquals($file1->id(), $file2->id(), 'Files have the same ids');
    $this->assertEquals($file1->getFileUri(), $file2->getFileUri(), 'Files have the same path');
  }

  /**
   * Creates and saves a file, asserting that it was saved.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   *
   * @return \Drupal\file\FileInterface
   *   File entity.
   */
  public function createFile($filepath = NULL, $contents = NULL, $scheme = NULL) {
    // Don't count hook invocations caused by creating the file.
    \Drupal::state()->set('file_test.count_hook_invocations', FALSE);
    $file = File::create([
      'uri' => $this->createUri($filepath, $contents, $scheme),
      'uid' => 1,
    ]);
    $file->save();
    // Write the record directly rather than using the API so we don't invoke
    // the hooks.
    // Verify that the file was added to the database.
    $this->assertGreaterThan(0, $file->id());

    \Drupal::state()->set('file_test.count_hook_invocations', TRUE);
    return $file;
  }

  /**
   * Creates a file and returns its URI.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   *
   * @return string
   *   File URI.
   */
  public function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      // cSpell:disable-next-line
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
    }
    if (!isset($scheme)) {
      $scheme = 'public';
    }
    $filepath = $scheme . '://' . $filepath;

    if (!isset($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertFileExists($filepath);
    return $filepath;
  }

}
