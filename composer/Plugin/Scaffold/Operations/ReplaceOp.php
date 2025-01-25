<?php

namespace Drupal\Composer\Plugin\Scaffold\Operations;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath;
use Drupal\Composer\Plugin\Scaffold\ScaffoldOptions;

/**
 * Scaffold operation to copy or symlink from source to destination.
 *
 * @internal
 */
class ReplaceOp extends AbstractOperation {

  /**
   * Identifies Replace operations.
   */
  const ID = 'replace';

  /**
   * The relative path to the source file.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath
   */
  protected $source;

  /**
   * Whether to overwrite existing files.
   *
   * @var bool
   */
  protected $overwrite;

  /**
   * Constructs a ReplaceOp.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $sourcePath
   *   The relative path to the source file.
   * @param bool $overwrite
   *   Whether to allow this scaffold file to overwrite files already at
   *   the destination. Defaults to TRUE.
   */
  public function __construct(ScaffoldFilePath $sourcePath, $overwrite = TRUE) {
    $this->source = $sourcePath;
    $this->overwrite = $overwrite;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateContents() {
    return file_get_contents($this->source->fullPath());
  }

  /**
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $fs = new Filesystem();
    $destination_path = $destination->fullPath();
    $interpolator = $destination->getInterpolator();
    // Do nothing if overwrite is 'false' and a file already exists at the
    // destination.
    if ($this->overwrite === FALSE && file_exists($destination_path)) {
      $io->write($interpolator->interpolate("  - Skip <info>[dest-rel-path]</info> because it already exists and overwrite is <comment>false</comment>."));
      return new ScaffoldResult($destination, FALSE);
    }

    // Process destination permissions.
    $this->processDestinationPermissions(dirname($destination_path), $io, $interpolator);

    // Remove the destination if it exists,
    // and ensure the destination directory exists.
    $fs->remove($destination_path);
    $fs->ensureDirectoryExists(dirname($destination_path));

    if ($options->symlink()) {
      return $this->symlinkScaffold($destination, $io);
    }

    return $this->copyScaffold($destination, $io);
  }

  /**
   * Allow full control to the destination before the file manipulation.
   *
   * @param string $destination_path
   *   Path to the destination file.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface for output.
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath\Interpolator $interpolator
   *   The interpolator for writing messages.
   */
  public function processDestinationPermissions(string $destination_path, IOInterface $io, $interpolator): void {
    if (file_exists($destination_path)) {
      try {
        chmod($destination_path, 0744);
      }
      catch (\Exception $e) {
        $io->write($interpolator->interpolate("Destination overwrite failed due to: " . $e->getMessage()));
      }
    }
    else {
      $io->write($interpolator->interpolate("Skipped setting permissions: $destination_path does not exist."));
    }
  }

  /**
   * Copies the scaffold file.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file to process.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult
   *   The scaffold result.
   */
  protected function copyScaffold(ScaffoldFilePath $destination, IOInterface $io) {
    $interpolator = $destination->getInterpolator();
    $this->source->addInterpolationData($interpolator);
    $success = file_put_contents($destination->fullPath(), $this->contents());
    if ($success === FALSE) {
      throw new \RuntimeException($interpolator->interpolate("Could not copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!"));
    }
    $io->write($interpolator->interpolate("  - Copy <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>"));
    return new ScaffoldResult($destination, $this->overwrite);
  }

  /**
   * Symlinks the scaffold file.
   *
   * @param \Drupal\Composer\Plugin\Scaffold\ScaffoldFilePath $destination
   *   Scaffold file to process.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldResult
   *   The scaffold result.
   */
  protected function symlinkScaffold(ScaffoldFilePath $destination, IOInterface $io) {
    $interpolator = $destination->getInterpolator();
    try {
      $fs = new Filesystem();
      $fs->relativeSymlink($this->source->fullPath(), $destination->fullPath());
      if (!is_link($destination->fullPath())) {
        throw new \RuntimeException($interpolator->interpolate("Failed to create symlink from <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>."));
      }
    }
    catch (\Exception $e) {
      throw new \RuntimeException($interpolator->interpolate("Could not symlink source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!"), [], $e);
    }
    $io->write($interpolator->interpolate("  - Link <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>"));
    return new ScaffoldResult($destination, $this->overwrite);
  }

}
