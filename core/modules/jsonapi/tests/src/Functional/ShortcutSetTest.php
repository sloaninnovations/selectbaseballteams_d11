<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\shortcut\Entity\ShortcutSet;

/**
 * JSON:API integration test for the "ShortcutSet" config entity type.
 *
 * @group jsonapi
 * @group #slow
 */
class ShortcutSetTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['shortcut'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'shortcut_set';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'shortcut_set--shortcut_set';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access shortcuts']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['access shortcuts', 'customize shortcut links']);
        break;

      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer shortcuts']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access shortcuts' permission is required.";

      case 'POST':
        return "The 'administer shortcuts' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $set = ShortcutSet::create([
      'id' => 'llama-set',
      'label' => 'Llama Set',
    ]);
    $set->save();
    return $set;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/shortcut_set/shortcut_set/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'shortcut_set--shortcut_set',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'label' => 'Llama Set',
          'status' => TRUE,
          'langcode' => 'en',
          'dependencies' => [],
          'drupal_internal__id' => 'llama-set',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 'special';

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'shortcut_set--shortcut_set',
        'attributes' => [
          'drupal_internal__id' => 'special',
          'label' => 'My special shortcut set',
        ],
      ],
    ];
  }

}
