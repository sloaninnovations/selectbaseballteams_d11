<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;

/**
 * Checks that at least one of the given constraint is satisfied.
 *
 * Overrides the symfony constraint to convert the array of constraints to array of constraint objects and use them.
 */
#[Constraint(
  id: 'AtLeastOneOf',
  label: new TranslatableMarkup('At Least One Of', [], ['context' => 'Validation'])
)]
class AtLeastOneOfConstraint extends AtLeastOneOf implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $constraint_manager = $container->get('validation.constraint');
    $constraints = $configuration['constraints'];
    foreach ($constraints as $constraint_id => $constraint) {
      foreach ($constraint as $constraint_name => $constraint_options) {
        $constraints[$constraint_id] = $constraint_manager->create($constraint_name, $constraint_options);
      }
    }
    // @todo Setting [] for $groups as RecursiveContextualValidator doesn't allow groups. Figure out a better solution for this.
    return new static($constraints, []);
  }

}
