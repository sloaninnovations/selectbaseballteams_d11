<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Scaffold\Integration;

use Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\Scaffold\Operations\ReplaceOp
 *
 * @group Scaffold
 */
class ReplaceOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess(): void {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $source = $fixtures->sourcePath('drupal-assets-fixture', 'robots.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new ReplaceOp($source, TRUE);
    // Assert that destination directory exists.
    $this->prepareDirectory(dirname($destination->fullPath()));
    // Assert that there is no target file before we run our test.
    $this->assertFileDoesNotExist($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold file.
    $contents = trim(file_get_contents($destination->fullPath()));
    $this->assertEquals('# Test version of robots.txt from drupal/core.', $contents);
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Copy [web-root]/robots.txt from assets/robots.txt', $output);
  }

  /**
   * @covers ::process
   */
  public function testEmptyFile(): void {
    $fixtures = new Fixtures();
    $destination = $fixtures->destinationPath('[web-root]/empty_file.txt');
    $source = $fixtures->sourcePath('empty-file', 'empty_file.txt');
    $options = ScaffoldOptions::create([]);
    $sut = new ReplaceOp($source, TRUE);
    // Assert that destination directory exists.
    $this->prepareDirectory(dirname($destination->fullPath()));
    // Assert that there is no target file before we run our test.
    $this->assertFileDoesNotExist($destination->fullPath());
    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);
    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());
    // Assert the target contained the contents from the correct scaffold file.
    $this->assertSame('', file_get_contents($destination->fullPath()));
    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertStringContainsString('Copy [web-root]/empty_file.txt from assets/empty_file.txt', $output);
  }

  /**
   * Ensures the specified directory exists and is writable.
   *
   * Creates the directory if it doesn’t already exist.
   *
   * @param string $path
   *   The directory path to check or create.
   *
   * @throws \RuntimeException
   *   If the directory cannot be created or isn’t writable.
   */
  public function prepareDirectory(string $path): void {
    if (!is_dir($path)) {
      if (!mkdir($path, 0744, TRUE) && !is_dir($path)) {
        throw new \RuntimeException("Failed to create directory: $path");
      }
    }

    if (!is_writable($path)) {
      throw new \RuntimeException("Directory is not writable: $path");
    }
  }

}
