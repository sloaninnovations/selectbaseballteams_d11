<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Graph\Graph;

/**
 * Resolves the dependencies of asset (CSS/JavaScript) libraries.
 */
class LibraryDependencyResolver implements LibraryDependencyResolverInterface {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The libraries graph.
   *
   * @var array
   */
  protected $librariesGraph = [];

  /**
   * Constructs a new LibraryDependencyResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery) {
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesWithDependencies(array $libraries) {
    $this->librariesGraph = $this->doGetDependencies($libraries);
    $this->doProcessBeforeAfter();

    $graph_object = new Graph($this->librariesGraph);
    $graph = $graph_object->searchAndSort();

    uasort($graph, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? 1 : -1;
    });

    return array_keys($graph);
  }

  /**
   * Gets the given libraries with its dependencies.
   *
   * Helper method for ::getLibrariesWithDependencies().
   *
   * @param string[] $libraries_with_unresolved_dependencies
   *   A list of libraries, with unresolved dependencies, in the order they
   *   should be loaded.
   * @param array $graph
   *   The graph of libraries that is being built recursively.
   *
   * @return string[]
   *   A list of libraries, in the order they should be loaded, including their
   *   dependencies.
   */
  protected function doGetDependencies(array $libraries_with_unresolved_dependencies, array $graph = []) {
    foreach ($libraries_with_unresolved_dependencies as $library) {
      if (!isset($graph[$library])) {
        [$extension, $name] = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if ($definition) {
          $graph[$library]['edges'] = [];
        }

        if (!empty($definition['dependencies'])) {
          foreach ($definition['dependencies'] as $dependency) {
            $graph[$library]['edges'][$dependency] = $dependency;
          }

          $graph = $this->doGetDependencies($definition['dependencies'], $graph);
        }
      }
    }
    return $graph;
  }

  /**
   * Processed before/after settings for the libraries graph.
   *
   * Helper method for ::getLibrariesWithDependencies().
   */
  protected function doProcessBeforeAfter(): void {
    foreach ($this->librariesGraph as $library => $data) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name) + [
        'after' => [],
        'before' => [],
      ];

      foreach ($definition['after'] as $after) {
        if (isset($this->librariesGraph[$after])) {
          $this->librariesGraph[$library]['edges'][$after] = $after;
        }
      }

      foreach ($definition['before'] as $before) {
        if (isset($this->librariesGraph[$before])) {
          $this->librariesGraph[$before]['edges'][$library] = $library;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMinimalRepresentativeSubset(array $libraries) {
    assert(count($libraries) === count(array_unique($libraries)), '$libraries can\'t contain duplicate items.');

    $this->librariesGraph = $this->doGetDependencies($libraries);
    $this->doProcessBeforeAfter();

    $libraries_to_exclude = [];
    foreach ($this->librariesGraph as $vertex) {
      $libraries_to_exclude += $vertex['edges'];
    }

    return array_values(array_diff($libraries, $libraries_to_exclude));
  }

}
