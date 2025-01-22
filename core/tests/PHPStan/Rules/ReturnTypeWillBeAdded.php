<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\ParserNodeTypeToPHPStanType;

/**
 * Check methods for future return type addition.
 *
 * @implements Rule<\PHPStan\Node\InClassMethodNode>
 *
 * @internal
 */
final class ReturnTypeWillBeAdded implements Rule {

  public function __construct(
    private readonly FileTypeMapper $fileTypeMapper,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return InClassMethodNode::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {

    $method = $node->getMethodReflection();
    $methodName = $method->getName();

    // Skip magic methods.
    if ($node->getOriginalNode()->isMagic()) {
      return [];
    }

    // Check that there are no duplicate '@return-type-will-be-added'
    // annotations on the method.
    $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
      $scope->getFile(),
      $scope->isInClass() ? $scope->getClassReflection()->getName() : NULL,
      $scope->isInTrait() ? $scope->getTraitReflection()->getName() : NULL,
      $methodName,
      $method->getDocComment() ?? '',
    );
    try {
      $returnTypeWillBeAddedTag = $this->getReturnTypeWillBeAddedTag($resolvedPhpDoc);
    }
    catch (\LogicException $e) {
      $message = sprintf(
        "%s::%s() %s",
        $method->getDeclaringClass()->getName(),
        $method->getName(),
        $e->getMessage(),
      );
      return [
        RuleErrorBuilder::message($message)
          ->line($node->getStartLine())
          ->identifier('drupal.returnTypeWillBeAdded')
          ->build(),
      ];
    }

    // Determine the native return type of the method.
    $methodNativeParserReturnType = $node->getOriginalNode()->getReturnType();
    // @phpstan-ignore phpstanApi.method
    $methodNativeReturnType = $methodNativeParserReturnType ? ParserNodeTypeToPHPStanType::resolve($methodNativeParserReturnType, $scope->getClassReflection()) : NULL;

    // Determine the PhpDoc return type of the method.
    $methodPhpDocReturnTag = $resolvedPhpDoc->getReturnTag();
    $methodPhpDocReturnType = $methodPhpDocReturnTag ? $methodPhpDocReturnTag->getType() : NULL;

    // Annotation @return-type-will-be-added exists, but method already has a
    // native return type.
    if ($returnTypeWillBeAddedTag && $methodNativeReturnType) {
      $message = sprintf(
        "%s::%s() specifies a '@return-type-will-be-added' annotation, but already has a '%s' native return type.",
        $method->getDeclaringClass()->getName(),
        $method->getName(),
        $methodNativeReturnType->describe(VerbosityLevel::value()),
      );
      return [
        RuleErrorBuilder::message($message)
          ->line($node->getStartLine())
          ->identifier('drupal.returnTypeWillBeAdded')
          ->build(),
      ];
    }

    // Annotation @return-type-will-be-added exists, but no @return.
    if ($returnTypeWillBeAddedTag && $methodPhpDocReturnType === NULL) {
      $message = sprintf(
        "%s::%s() specifies a '@return-type-will-be-added' annotation, but a '@return' annotation is missing.",
        $method->getDeclaringClass()->getName(),
        $method->getName(),
      );
      return [
        RuleErrorBuilder::message($message)
          ->line($node->getStartLine())
          ->identifier('drupal.returnTypeWillBeAdded')
          ->build(),
      ];
    }

    // If method is declared in current class, we are done. We only continue
    // checks for methods prototyped elsewhere.
    $prototypeClass = $method->getPrototype()->getDeclaringClass();
    if ($prototypeClass == $method->getDeclaringClass()) {
      return [];
    }
    $prototypeMethod = $prototypeClass->getMethod($methodName, $scope);
    $prototypeTrait = method_exists($prototypeMethod, 'getDeclaringTrait') ? $prototypeMethod->getDeclaringTrait() : NULL;

    // Get PHPDoc for the prototype.
    $resolvedPrototypePhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
      $prototypeClass->getFileName(),
      $prototypeClass->getName(),
      $prototypeTrait ? $prototypeTrait->getName() : NULL,
      $methodName,
      $prototypeMethod->getDocComment() ?? '',
    );

    // Check for '@return-type-will-be-added' annotation on the prototype.
    try {
      $prototypeReturnTypeWillBeAddedTag = $this->getReturnTypeWillBeAddedTag($resolvedPrototypePhpDoc);
    }
    catch (\LogicException) {
      $prototypeReturnTypeWillBeAddedTag = NULL;
      // Keep going, duplicates were reported on the prototype parsing already.
    }

    // No annotation in prototype, return.
    if ($prototypeReturnTypeWillBeAddedTag == NULL) {
      return [];
    }

    // Determine the PhpDoc return type of the method.
    $prototypeMethodPhpDocReturnTag = $resolvedPrototypePhpDoc->getReturnTag();
    $prototypeMethodPhpDocReturnType = $prototypeMethodPhpDocReturnTag ? $prototypeMethodPhpDocReturnTag->getType() : NULL;

    // Annotation in prototype, method has no native return type.
    if ($prototypeReturnTypeWillBeAddedTag && $methodNativeReturnType === NULL && $prototypeMethodPhpDocReturnType) {
      $message = sprintf(
        "%s::%s() will add '%s' as a native return type declaration %s. Add the return type to the implementation now.",
        $prototypeTrait ? $prototypeTrait->getName() : $prototypeClass->getName(),
        $methodName,
        $prototypeMethodPhpDocReturnType->describe(VerbosityLevel::value()),
        (string) $prototypeReturnTypeWillBeAddedTag->value ?: 'in the future',
      );
      return [
        RuleErrorBuilder::message($message)
          ->line($node->getStartLine())
          ->identifier('drupal.returnTypeWillBeAdded')
          ->build(),
      ];
    }

    // Annotation in prototype, method has a diverging native return type.
    if ($prototypeReturnTypeWillBeAddedTag &&
      $methodNativeReturnType &&
      $prototypeMethodPhpDocReturnType &&
      !$prototypeMethodPhpDocReturnType->isSuperTypeOf($methodNativeReturnType)->yes()
    ) {
      $message = sprintf(
        "Declaration of %s::%s(): %s must be compatible with %s::%s(): %s that will be added as a native return type declaration %s. Change the return type to the implementation now.",
        $method->getDeclaringClass()->getName(),
        $methodName,
        $methodNativeReturnType->describe(VerbosityLevel::value()),
        $prototypeTrait ? $prototypeTrait->getName() : $prototypeClass->getName(),
        $methodName,
        $prototypeMethodPhpDocReturnType->describe(VerbosityLevel::value()),
        (string) $prototypeReturnTypeWillBeAddedTag->value ?: 'in the future',
      );
      return [
        RuleErrorBuilder::message($message)
          ->line($node->getStartLine())
          ->identifier('drupal.returnTypeWillBeAdded')
          ->build(),
      ];
    }

    return [];
  }

  /**
   * Returns the @return-type-will-be-added tag if existing.
   *
   * @param \PHPStan\PhpDoc\ResolvedPhpDocBlock $resolvedPhpDoc
   *   The resolved PHPDoc block.
   *
   * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode|null
   *   A PHPDoc tag node containing the @return-type-will-be-added annotation.
   *
   * @throws \LogicException
   *   If multiple @return-type-will-be-added annotations are found in a single
   *   PHPDoc block.
   */
  private function getReturnTypeWillBeAddedTag(ResolvedPhpDocBlock $resolvedPhpDoc): ?PhpDocTagNode {
    $tags = [];
    foreach ($resolvedPhpDoc->getPhpDocNodes() as $node) {
      $tags = array_merge($tags, $node->getTagsByName('@return-type-will-be-added'));
    }
    if (($tagsCount = count($tags)) > 1) {
      throw new \LogicException("'@return-type-will-be-added' annotation should only be defined once. Found {$tagsCount} instances.");
    }
    return $tags[0] ?? NULL;
  }

}
