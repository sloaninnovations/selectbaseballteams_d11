<?php

namespace Drupal\Core\Template;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Theme\ComponentPluginManager;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\AbstractExpression;

/**
 * Represents a component embed node, based on twig embed node.
 */
#[YieldReady]
class ComponentEmbedNode extends EmbedNode
{

  protected string $component_id;
  protected string $variant;

    // we don't inject the module to avoid node visitors to traverse it twice (as it will be already visited in the main module)
    public function __construct(string $name, string $component_id, string $variant, int $index, ?AbstractExpression $variables, bool $only, bool $ignoreMissing, int $lineno, ?string $tag = null, protected ComponentPluginManager $pluginManager)
    {
        parent::__construct($name, $index, $variables, $only, $ignoreMissing, $lineno, $tag);
        $this->component_id = $component_id;
        $this->variant = $variant;
    }

    protected function addGetTemplate(Compiler $compiler): void
    {
      $component = $this->getComponent($this->component_id);
      $template = $component->getTemplatePath();
$n = $this->getTemplateName();
      $compiler
          ->write('$this->loadTemplate(')
          ->string($this->getAttribute('name'))
          ->raw(', ')
          ->repr($this->getTemplateName())
          ->raw(', ')
          ->repr($this->getTemplateLine())
          ->raw(', ')
          ->string($this->getAttribute('index'))
          ->raw(')')
      ;
    }

  protected function getComponent(string $component_id): ?Component {
    if (!preg_match('/^[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*:[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*$/', $component_id)) {
      return NULL;
    }
    try {
      return $this->pluginManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return NULL;
    }
  }

}
