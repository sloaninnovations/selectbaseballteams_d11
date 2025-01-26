<?php

declare(strict_types=1);

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

/**
 * Defines a base trait for automatically wiring dependency arguments.
 */
trait AutowireArgumentsTrait {

  /**
   * Instantiates a new instance of the implementing class using autowiring.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param ...$args
   *   Any predefined arguments to pass to the constructor.
   *
   * @return static
   */
  public static function autowireArguments(ContainerInterface $container, ...$args) {
    if (method_exists(static::class, '__construct')) {
      $constructor = new \ReflectionMethod(static::class, '__construct');
      foreach (array_slice($constructor->getParameters(), count($args)) as $parameter) {
        $service = ltrim((string) $parameter->getType(), '?');
        foreach ($parameter->getAttributes(Autowire::class) as $attribute) {
          $service = (string) $attribute->newInstance()->value;
        }

        if (!$container->has($service)) {
          throw new AutowiringFailedException($service, sprintf('Cannot autowire service "%s": argument "$%s" of method "%s::_construct()", you should configure its value explicitly.', $service, $parameter->getName(), static::class));
        }

        $args[] = $container->get($service);
      }
    }

    return new static(...$args);
  }

}
