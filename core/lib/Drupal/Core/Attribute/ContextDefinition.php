<?php

namespace Drupal\Core\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\Plugin\Context\ContextDefinition as PluginContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @defgroup plugin_context Attribute for context definition
 * @{
 * Describes how to use ContextDefinition attribute.
 *
 * When providing plugin attributes, contexts can be defined to support UI
 * interactions through providing limits, and mapping contexts to appropriate
 * plugins. Context definitions can be provided as such:
 * @code
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * @endcode
 *
 * To add a label to a context definition use the "label" key:
 * @code
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * @endcode
 *
 * Contexts are required unless otherwise specified. To make an optional
 * context use the "required" key:
 * @code
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE, label =
 *   @Translation("Node"))
 *   }
 * @endcode
 *
 * To define multiple contexts, simply provide different key names in the
 * context array:
 * @code
 *   context_definitions = {
 *     "artist" = @ContextDefinition("entity:node", label =
 *   @Translation("Artist")),
 *     "album" = @ContextDefinition("entity:node", label =
 *   @Translation("Album"))
 *   }
 * @endcode
 *
 * Specifying a default value for the context definition:
 * @code
 *   context_definitions = {
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       default_value = @Translation("Checkout complete! Thank you for your
 *   purchase.")
 *     )
 *   }
 * @endcode
 *
 * @see attribute
 *
 * @}
 */

/**
 * Defines a context definition attribute.
 *
 * Some plugins require various data contexts in order to function. This class
 * supports that need by allowing the contexts to be easily defined within an
 * attribute and return a ContextDefinitionInterface implementing class.
 *
 * @ingroup plugin_context
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ContextDefinition extends Plugin {

  /**
   * The ContextDefinitionInterface object.
   */
  protected ContextDefinitionInterface $definition;

  /**
   * Constructs a ContextDefinition attribute.
   *
   * @param string $id
   *   The attribute class ID.
   * @param string $value
   *   The required data type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The UI label of this context definition.
   * @param bool $required
   *   (optional) Whether the context definition is required.
   * @param bool $multiple
   *   (optional) Whether the context definition is multivalue.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The UI description of this context definition.
   * @param string|null $defaultValue
   *   (optional) The default value in case the underlying value is not set.
   * @param string|null $type
   *   (optional) A custom ContextDefinitionInterface class.
   * @param array $constraints
   *   (optional) An array of validation constraints.
   *
   * @throws \Exception
   */
  public function __construct(
    public readonly string $id,
    public readonly string $value,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly bool $required = TRUE,
    public readonly bool $multiple = FALSE,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $defaultValue = NULL,
    public readonly ?string $type = NULL,
    public readonly array $constraints = [],
  ) {
    if (!\is_null($type) && !in_array(ContextDefinitionInterface::class, class_implements($type))) {
      throw new \Exception('ContextDefinition class must implement \Drupal\Core\Plugin\Context\ContextDefinitionInterface.');
    }

    $class = $this->getDefinitionClass($value, $type);
    $this->definition = new $class(
      $value,
      $label,
      $required,
      $multiple,
      $description,
      $defaultValue
    );

    foreach ($constraints as $constraint_name => $options) {
      $this->definition->addConstraint($constraint_name, $options);
    }
  }

  /**
   * Determines the context definition class to use.
   *
   * If the annotation specifies a specific context definition class, we use
   * that. Otherwise, we use \Drupal\Core\Plugin\Context\EntityContextDefinition
   * if the data type starts with 'entity:', since it contains specialized logic
   * specific to entities. Otherwise, we fall back to the generic
   * \Drupal\Core\Plugin\Context\ContextDefinition class.
   *
   * @param string $value
   *   The required data type.
   * @param string|null $type
   *   (optional) A custom ContextDefinitionInterface class.
   *
   * @return string
   *   The fully-qualified name of the context definition class.
   */
  protected function getDefinitionClass(string $value, ?string $type = NULL): string {
    return $type ?? match (TRUE) {
      str_starts_with($value, 'entity:') => EntityContextDefinition::class,
      default => PluginContextDefinition::class,
    };
  }

  /**
   * Returns the value of an annotation.
   */
  public function get(): ContextDefinitionInterface {
    return $this->definition;
  }

}
