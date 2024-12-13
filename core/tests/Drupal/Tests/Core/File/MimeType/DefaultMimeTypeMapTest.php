<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\File\MimeType;

use Drupal\Core\File\MimeType\DefaultMimeTypeMap;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MIME type mapper to extension.
 *
 * @coversDefaultClass \Drupal\Core\File\MimeType\DefaultMimeTypeMap
 *
 * @group File
 */
class DefaultMimeTypeMapTest extends UnitTestCase {

  /**
   * The default MIME type map under test.
   */
  protected DefaultMimeTypeMap $map;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->map = new DefaultMimeTypeMap();
  }

  /**
   * Sets up a very basic mapping array for testing.
   */
  protected function setBasicMapping(): void {
    $this->map->addMapping('application/java-archive', 'jar');
    $this->map->addMapping('image/jpeg', 'jpg');
  }

  /**
   * @covers ::addMapping
   */
  public function testAddMapping(): void {
    $this->setBasicMapping();

    $this->map->addMapping('image/gif', 'gif');
    $this->assertEquals(
      'image/gif',
      $this->map->getMimeTypeForExtension('gif')
    );

    $this->map->addMapping('image/jpeg', 'jpeg');
    $this->assertEquals(
      'image/jpeg',
      $this->map->getMimeTypeForExtension('jpeg')
    );
  }

  /**
   * @covers ::removeMapping
   */
  public function testRemoveMapping(): void {
    $this->setBasicMapping();
    $this->assertTrue($this->map->removeMapping('image/jpeg', 'jpg'));
    $this->assertNull($this->map->getMimeTypeForExtension('jpg'));
    $this->assertFalse($this->map->removeMapping('bar', 'foo'));
  }

  /**
   * @covers ::removeMimeType
   */
  public function testRemoveMimeType(): void {
    $this->setBasicMapping();

    $this->assertTrue($this->map->removeMimeType('image/jpeg'));
    $this->assertNull($this->map->getMimeTypeForExtension('jpg'));
    $this->assertFalse($this->map->removeMimeType('foo/bar'));
  }

  /**
   * @covers ::listMimeTypes
   */
  public function testListMimeTypes(): void {
    $this->setBasicMapping();
    $this->assertEquals(['application/java-archive', 'image/jpeg'],
      $this->map->listMimeTypes());
  }

  public function testHasMimeType(): void {
    $this->setBasicMapping();
    $this->assertTrue($this->map->hasMimeType('image/jpeg'));
    $this->assertFalse($this->map->hasMimeType('foo/bar'));
  }

  /**
   * @covers ::getMimeTypeForExtension
   */
  public function testGetMimeTypeForExtension(): void {
    $this->map->loadDefault();
    $this->assertSame('image/jpeg', $this->map->getMimeTypeForExtension('jpe'));
  }

  /**
   * @covers ::getExtensionsForMimeType
   */
  public function testGetExtensionsForMimeType(): void {
    $this->map->loadDefault();
    $this->assertEquals(['jpe', 'jpeg', 'jpg'],
      $this->map->getExtensionsForMimeType('image/jpeg'));
  }

  /**
   * @covers ::listExtensions
   */
  public function testListExtension(): void {
    $this->setBasicMapping();
    $this->assertEquals(['jar', 'jpg'],
      $this->map->listExtensions());
  }

  public function testHasExtension(): void {
    $this->setBasicMapping();
    $this->assertTrue($this->map->hasExtension('jpg'));
    $this->assertFalse($this->map->hasExtension('foo'));
  }

}
