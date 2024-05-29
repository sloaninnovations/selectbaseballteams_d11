<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SequenceKeysConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    private readonly ConstraintManager $constraintManager,
    private readonly ClassResolverInterface $classResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('validation.constraint'),
      $container->get(ClassResolverInterface::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint) {
    assert($constraint instanceof SequenceKeysConstraint);

    if (!is_array($value)) {
      throw new UnexpectedTypeException($value, 'array');
    }

    if (empty($value)) {
      return;
    }

    $key_constraints = [];
    foreach ($constraint->constraints as $name => $options) {
      $key_constraints[$name] = $this->constraintManager->create($name, $options);
    }
    $constraint_validator_factory = new ConstraintValidatorFactory($this->classResolver);

    foreach (array_keys($value) as $key) {
      foreach ($key_constraints as $key_constraint) {
        $this->context->setConstraint($key_constraint);
        $validator = $constraint_validator_factory->getInstance($key_constraint);
        $validator->initialize($this->context);
        $validator->validate($key, $key_constraint);
      }
    }
  }

}
