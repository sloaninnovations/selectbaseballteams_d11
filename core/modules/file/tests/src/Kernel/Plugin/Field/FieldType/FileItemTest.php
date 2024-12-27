<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the file field type.
 *
 * @group file
 */
class FileItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'field', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testGenerateSampleValues(): void {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    // Create a definition that does not include a file directory.
    $definition->expects($this->any())
      ->method('getSettings')
      ->willReturn([
        'file_extensions' => 'txt',
        'file_directory' => '',
        'uri_scheme' => 'public',
        'display_default' => TRUE,
      ]);
    $value = FileItem::generateSampleValue($definition);
    $this->assertNotEmpty($value);

    $fid = $value['target_id'];
    $file = File::load($fid);
    $fileUri = $file->getFileUri();

    // Confirm there are only two forward slashes.
    $this->assertMatchesRegularExpression('#^public://[^/]#', $fileUri);
  }

}
