<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;

/**
 * General functional test class.
 *
 * @group jsonapi
 * @group legacy
 *
 * @internal
 */
class JsonApiFunctionalEntityQueryAlterTest extends JsonApiFunctionalTestBase {

  /**
   * @var string[]
   */
  protected static $modules = [
    'jsonapi_test_entity_query_alter',
    'page_cache',
  ];

  /**
   * Test the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(2, 2, TRUE, TRUE, static::IS_NOT_MULTILINGUAL, FALSE);
    // 0. HEAD request allows a client to verify that JSON:API is installed and no major error is introduced
    // by the query alterer.
    $this->httpClient->request('HEAD', $this->buildUrl('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);

    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(2, count($collection_output['data']));

    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'sort' => 'drupal_internal__nid',
        'custom_nid' => 1,
      ],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(1, count($collection_output['data']));
    $this->assertEquals(1, $collection_output['data'][0]['attributes']['drupal_internal__nid']);

    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'sort' => 'drupal_internal__nid',
        'custom_nid' => 2,
      ],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(1, count($collection_output['data']));
    $this->assertEquals(2, $collection_output['data'][0]['attributes']['drupal_internal__nid']);

  }

}
