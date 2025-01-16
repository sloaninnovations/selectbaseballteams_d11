<?php

namespace Drupal\Core\Utility;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a way to build external or non Drupal local domain URLs.
 *
 * It takes into account configured safe HTTP protocols.
 */
class UnroutedUrlAssembler implements UnroutedUrlAssemblerInterface {

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The outbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a new unroutedUrlAssembler object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   * @param \Drupal\Core\PathProcessor\OutboundPathProcessorInterface $path_processor
   *   The output path processor.
   * @param string[] $filter_protocols
   *   (optional) An array of protocols allowed for URL generation.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(RequestStack $request_stack, OutboundPathProcessorInterface $path_processor, array $filter_protocols = ['http', 'https'], RequestContext $request_context = NULL) {
    UrlHelper::setAllowedProtocols($filter_protocols);
    $this->requestStack = $request_stack;
    $this->pathProcessor = $path_processor;
    if (!$request_context) {
      @trigger_error(sprintf('Invoking %s without $request_context is deprecated in drupal:10.3.0 and unsupported in drupal:11.0.0. See https://www.drupal.org/node/3268509', __FUNCTION__), E_USER_DEPRECATED);
      $request_context = \Drupal::service('router.request_context');
    }
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   *
   * This is a helper function that calls buildExternalUrl() or buildLocalUrl()
   * based on a check of whether the path is a valid external URL.
   */
  public function assemble($uri, array $options = [], $collect_bubbleable_metadata = FALSE) {
    // Note that UrlHelper::isExternal will return FALSE if the $uri has a
    // disallowed protocol.  This is later made safe since we always add at
    // least a leading slash.
    if (parse_url($uri, PHP_URL_SCHEME) === 'base') {
      return $this->buildLocalUrl($uri, $options, $collect_bubbleable_metadata);
    }
    elseif (UrlHelper::isExternal($uri)) {
      // UrlHelper::isExternal() only returns true for safe protocols.
      return $this->buildExternalUrl($uri, $options, $collect_bubbleable_metadata);
    }
    throw new \InvalidArgumentException("The URI '$uri' is invalid. You must use a valid URI scheme. Use base: for a path, e.g., to a Drupal file that needs the base path. Do not use this for internal paths controlled by Drupal.");
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExternalUrl($uri, array $options = [], $collect_bubbleable_metadata = FALSE) {
    $this->addOptionDefaults($options);
    // Split off the query & fragment.
    $parsed = UrlHelper::parse($uri);
    $uri = $parsed['path'];

    $parsed += ['query' => []];
    $options += ['query' => []];

    $options['query'] = NestedArray::mergeDeepArray([$parsed['query'], $options['query']], TRUE);

    if ($parsed['fragment'] && !$options['fragment']) {
      $options['fragment'] = '#' . $parsed['fragment'];
    }

    if (isset($options['https'])) {
      if ($options['https'] === TRUE) {
        $uri = str_replace('http://', 'https://', $uri);
      }
      elseif ($options['https'] === FALSE) {
        $uri = str_replace('https://', 'http://', $uri);
      }
    }
    // Allow (outbound) path processing, if needed.
    $base_url = $this->requestContext->getCompleteBaseUrl();
    if (!array_key_exists('path_processing', $options) && UrlHelper::externalIsLocal($uri, $base_url)) {
      // Match UrlGenerator::generateFromRoute()'s default.
      $options['path_processing'] = TRUE;
    }
    if (!empty($options['path_processing'])) {
      // @see ::buildLocalUrl().
      $uri = $base_url . $this->pathProcessor->processOutbound(
        substr($uri, strlen($base_url)),
        $options
      );
    }
    // Append the query.
    if ($options['query']) {
      $uri .= '?' . UrlHelper::buildQuery($options['query']);
    }
    // Reassemble.
    $url = $uri . $options['fragment'];
    return $collect_bubbleable_metadata ? (new GeneratedUrl())->setGeneratedUrl($url) : $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLocalUrl($uri, array $options = [], $collect_bubbleable_metadata = FALSE) {
    $generated_url = $collect_bubbleable_metadata ? new GeneratedUrl() : NULL;

    $this->addOptionDefaults($options);
    $request = $this->requestStack->getCurrentRequest();

    // Remove the base: scheme.
    // @todo Consider using a class constant for this in
    //   https://www.drupal.org/node/2417459
    $uri = substr($uri, 5);

    // Allow (outbound) path processing, if needed. A valid use case is the path
    // alias overview form:
    // @see \Drupal\path\Controller\PathController::adminOverview().
    if (!empty($options['path_processing'])) {
      // Do not pass the request, since this is a special case and we do not
      // want to include e.g. the request language in the processing.
      $uri = $this->pathProcessor->processOutbound($uri, $options, NULL, $generated_url);
    }
    // Strip leading slashes from internal paths to prevent them becoming
    // external URLs without protocol. /example.com should not be turned into
    // //example.com.
    $uri = ltrim($uri, '/');

    // Add any subdirectory where Drupal is installed.
    $current_base_path = $request->getBasePath() . '/';

    if ($options['absolute']) {
      $current_base_url = $request->getSchemeAndHttpHost() . $current_base_path;
      if (isset($options['https'])) {
        if (!empty($options['https'])) {
          $base = str_replace('http://', 'https://', $current_base_url);
          $options['absolute'] = TRUE;
        }
        else {
          $base = str_replace('https://', 'http://', $current_base_url);
          $options['absolute'] = TRUE;
        }
      }
      else {
        $base = $current_base_url;
      }
      if ($collect_bubbleable_metadata) {
        $generated_url->addCacheContexts(['url.site']);
      }
    }
    else {
      $base = $current_base_path;
    }

    $prefix = empty($uri) ? rtrim($options['prefix'], '/') : $options['prefix'];

    $uri = str_replace('%2F', '/', rawurlencode($prefix . $uri));
    $query = $options['query'] ? ('?' . UrlHelper::buildQuery($options['query'])) : '';
    $url = $base . $options['script'] . $uri . $query . $options['fragment'];
    return $collect_bubbleable_metadata ? $generated_url->setGeneratedUrl($url) : $url;
  }

  /**
   * Merges in default defaults.
   *
   * @param array $options
   *   The options to merge in the defaults.
   */
  protected function addOptionDefaults(array &$options) {
    $request = $this->requestStack->getCurrentRequest();
    $current_base_path = $request->getBasePath() . '/';
    $current_script_path = '';
    $base_path_with_script = $request->getBaseUrl();

    // If the current request was made with the script name (eg, index.php) in
    // it, then extract it, making sure the leading / is gone, and a trailing /
    // is added, to allow simple string concatenation with other parts.
    if (!empty($base_path_with_script)) {
      $script_name = $request->getScriptName();
      if (str_contains($base_path_with_script, $script_name)) {
        $current_script_path = ltrim(substr($script_name, strlen($current_base_path)), '/') . '/';
      }
    }

    // Merge in defaults.
    $options += [
      'fragment' => '',
      'query' => [],
      'absolute' => FALSE,
      'prefix' => '',
      'script' => $current_script_path,
    ];

    if (isset($options['fragment']) && $options['fragment'] !== '') {
      $options['fragment'] = '#' . $options['fragment'];
    }
  }

}
