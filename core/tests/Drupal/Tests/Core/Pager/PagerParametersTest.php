<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Pager;

use Drupal\Core\Pager\PagerParameters;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Pager\PagerParameters
 * @group Pager
 */
class PagerParametersTest extends UnitTestCase {

  /**
   * @covers ::getQueryParameters
   */
  public function testGetQueryParameters(): void {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $parameters = new PagerParameters($request_stack);
    $query = $request_stack->getCurrentRequest()->query;
    $this->assertEquals([], $parameters->getQueryParameters());
    $query->set('page', 1);
    $this->assertEquals(['page' => 1], $query->all(), 'page query set correctly');
    $this->assertEquals([], $parameters->getQueryParameters(), 'Pager filtered from empty parameters');
    $query->set('test', 1);
    $this->assertEquals(['page' => 1, 'test' => 1], $query->all(), 'test value set correctly');
    $this->assertEquals(['test' => 1], $parameters->getQueryParameters(), 'Pager filtered with another parameter');
  }

  /**
   * @covers ::findPage
   * @dataProvider providePagerQueries
   */
  public function testFindPage($raw_query, $parameter, $expected_query): void {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $parameters = new PagerParameters($request_stack);
    $request_stack->getCurrentRequest()->query->set('page', $raw_query);
    // Ensure findPage finds 0 when the query is actually empty or invalid.
    $expected_query = $expected_query ?: [0];
    foreach ($expected_query as $key => $value) {
      $this->assertSame($value, $parameters->findPage($key));
    }
  }

  /**
   * @covers ::getPagerQuery
   * @dataProvider providePagerQueries
   */
  public function testGetPagerQuery($raw_query, $parameter, $expected_query): void {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $parameters = new PagerParameters($request_stack);
    $request_stack->getCurrentRequest()->query->set('page', $raw_query);
    $this->assertEquals($expected_query, $parameters->getPagerQuery());
  }

  /**
   * Ensure missing request is handled cleanly.
   *
   * @covers ::getPagerParameter
   */
  public function testGetPagerParameterNoRequest(): void {
    $request_stack = new RequestStack();
    $parameters = new PagerParameters($request_stack);
    $this->assertSame('', $parameters->getPagerParameter());
  }

  /**
   * @covers ::getPagerParameter
   * @dataProvider providePagerQueries
   */
  public function testGetPagerParameter($raw_query, $parameter): void {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $parameters = new PagerParameters($request_stack);
    $request_stack->getCurrentRequest()->query->set('page', $raw_query);
    $this->assertSame($parameter, $parameters->getPagerParameter());
  }

  /**
   * Data provider for testGetPagerParameter(), testGetPagerQuery(), and testFindPage().
   *
   * @return array
   *   An associated array containing 3 keys:
   *   1. raw query
   *   2. parameter
   *   3. expected query
   */
  public static function providePagerQueries(): array {
    return [
      'defensive null page value' => [NULL, '', []],
      // Array values aren't supported, so they default to empty.
      'invalid empty page array' => [[], '', []],
      'invalid populated array' => [[1, 2, 3], '', []],
      // Nothing to check.
      'empty string' => ['', '', []],
      // Conventional but "zero" page values.
      'page 0 as a string' => ['0', '0', [0]],
      'page 0 as a integer' => [0, '0', [0]],
      // Conventional pager values.
      'page 1' => ['1', '1', [1]],
      'simple list of page values' => [
        '1,2,3,4',
        '1,2,3,4',
        [1, 2, 3, 4],
      ],
      'reversed list of page values' => [
        '4,3,2,1',
        '4,3,2,1',
        [4, 3, 2, 1],
      ],
    ];
  }

}
