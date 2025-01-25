<?php

namespace Drupal\Core\Pager;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides pager information contained within the current request.
 *
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
class PagerParameters implements PagerParametersInterface {

  /**
   * The HTTP request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct a PagerManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The current HTTP request stack.
   */
  public function __construct(RequestStack $stack) {
    $this->requestStack = $stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return UrlHelper::filterQueryParameters(
        $request->query->all(), ['page']
      );
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function findPage($pager_id = 0) {
    $pages = $this->getPagerQuery();
    return (int) ($pages[$pager_id] ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerQuery() {
    $query = $this->getPagerParameter();
    return $query !== '' ? array_map('intval', explode(',', $query)) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerParameter() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $params = $request->query->all();
      $page = $params['page'] ?? '';
      // The "page" can be an array so validate the type before casting,
      // i.e., "?page[offset]=12"
      return is_scalar($page) ? (string) $page : '';
    }
    return '';
  }

}
