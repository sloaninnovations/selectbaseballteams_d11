<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Component\Plugin\Attribute\PluginPropertyInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Attribute class for adding optional property values to plugin definitions.
 *
 * Because of the way plugin definitions are cached, subclasses of this class
 * can not be defined in modules. Instead, if properties should be added to a
 * plugin definition only if a module is installed, then this attribute class
 * should include the module in its module dependencies.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class PluginProperty extends AttributeBase implements PluginPropertyInterface {

  use DependencySerializationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|null
   */
  protected ?ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a plugin property object.
   *
   * @param int|string|array{int,string} $key
   *   The property key to set in the plugin in the definition. If the key is an
   *   array, it will be treated as a nested key, with the outermost keys coming
   *   first.
   * @param mixed $value
   *   The value to set the property to.
   * @param class-string[] $allowedPluginClasses
   *   List of plugin attribute class names that this property attribute can set
   *   properties on. If list is empty, property can be applied to any plugin.
   * @param string[] $moduleDependencies
   *   The property will be added to the plugin definition only if all the
   *   modules in this list are installed.
   * @param mixed $addToDefinitionCallback
   *   Callable that adds the property to the plugin definition. If NULL and the
   *   plugin definition is an array, the property will be set by nested key on
   *   the array. Callback function parameters are this plugin property
   *   attribute object and the plugin definition. Example:
   *   @code
   *    function exampleAddToDefinitionCallback(PluginProperty $attribute, array|object $definition): array|object {
   *      if (is_object($definition) and method_exists($definition, 'setProperty')) {
   *        $definition->setProperty($attribute->getKey(), $attribute->getValue());
   *      }
   *      return $definition;
   *    }
   *   @endcode
   */
  public function __construct(
    public readonly int|string|array $key,
    public readonly mixed $value,
    public readonly array $allowedPluginClasses = [],
    public readonly array $moduleDependencies = [],
    public mixed $addToDefinitionCallback = NULL,
  ) {
    if ($key === []) {
      throw new \InvalidArgumentException('Key can not be an empty array.');
    }
    // Can not serialize closures, so prevent their use.
    if ($this->addToDefinitionCallback instanceof \Closure) {
      throw new \InvalidArgumentException('Add to definition callback can not be a closure.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(): int|string|array {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(): mixed {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidPluginClass(string $plugin_class): bool {
    if (empty($this->allowedPluginClasses)) {
      return TRUE;
    }

    foreach ($this->allowedPluginClasses as $allowed_class) {
      if (is_a($plugin_class, $allowed_class, TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addToDefinition(array|object $definition): array|object {
    // Subclasses in core can override this ::addToDefinition() to work with
    // definitions that are objects, not array. When using this attribute to
    // set properties with a module (not core) provider, and the definition is
    // an object, a callback can be used to set the property on the definition.
    if (isset($this->addToDefinitionCallback)) {
      if (is_callable($this->addToDefinitionCallback)) {
        return ($this->addToDefinitionCallback)($this, $definition);
      }

      throw new InvalidPluginDefinitionException($definition['id'], 'Can not add property to plugin definition because specified addToDefinitionCallback is invalid.');
    }

    // Otherwise for array definitions, value will be set on the definition at
    // the nested array key.
    if (is_array($definition)) {
      $key = is_array($this->key) ? $this->key : [$this->key];
      NestedArray::setValue($definition, $key, $this->value);
    }
    return $definition;
  }

  /**
   * Getter for module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function getModuleHandler(): ModuleHandlerInterface {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * Injection setter for module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $moduleHandler): static {
    $this->moduleHandler = $moduleHandler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDependencies(): bool {
    return !empty($this->moduleDependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function hasMissingDependencies(): bool {
    if (!$this->hasDependencies()) {
      return FALSE;
    }
    // Check that the property attribute's module dependencies are installed.
    $installed_modules = array_keys($this->getModuleHandler()->getModuleList());
    return !empty(array_diff($this->moduleDependencies, $installed_modules));
  }

}
