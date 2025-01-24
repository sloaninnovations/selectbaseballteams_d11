<?php

declare(strict_types=1);

namespace Drupal\Component\Utility;

use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Parses a class file to find dependencies.
 *
 * Because reflection on classes that implement missing interfaces, extend
 * missing classes, or use missing traits can result in fatal errors or
 * exceptions being thrown, this parser can be used before reflection to
 * determine what those dependencies (interfaces, classes, and traits) are and
 * whether they are missing.
 */
class ClassDependenciesParser implements ClassDependenciesParserInterface {

  /**
   * PHP Parser object.
   *
   * Instantiating a parser is an expensive operation, and it can be reused
   * over multiple files, so instantiate once as a static property.
   *
   * @var \PhpParser\Parser
   *
   * @see https://github.com/nikic/PHP-Parser/blob/master/doc/component/Performance.markdown#object-reuse
   */
  protected static Parser $parser;

  /**
   * Whether the class has been parsed.
   *
   * @var bool
   */
  protected bool $parsed = FALSE;

  /**
   * The parsed class object.
   *
   * @var \PhpParser\Node\Stmt\Class_|null
   */
  protected ?Class_ $parsedClass = NULL;

  /**
   * Parsed class attributes.
   *
   * @var \PhpParser\Node\Attribute[]
   */
  protected array $classAttributes;

  /**
   * The dependencies for the class.
   *
   * Two-dimensional array of dependency names indexed by the type, such as
   * 'class' or 'interface' or 'trait'.
   *
   * @var string[][]
   */
  protected array $classDependencies;

  public function __construct(protected readonly \SplFileInfo $fileInfo) {}

  /**
   * Initiates the PHP parser.
   */
  protected function parse(): void {
    if ($this->parsed) {
      return;
    }

    $parser = static::getParser();
    $stmts = $parser->parse(file_get_contents($this->fileInfo->getPathname()));

    // Resolve all references to fully qualified names.
    $nameResolver = new NameResolver();
    $nodeTraverser = new NodeTraverser();
    $nodeTraverser->addVisitor($nameResolver);
    $stmts = $nodeTraverser->traverse($stmts);

    $nodeFinder = new NodeFinder();
    $this->parsedClass = $nodeFinder->findFirstInstanceOf($stmts, Class_::class);
    $this->parsed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasClassAttribute(string $attribute): bool {
    foreach ($this->getClassAttributes() as $classAttribute) {
      if (is_a((string) $classAttribute->name, $attribute, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassDependencies(): array {
    if (isset($this->classDependencies)) {
      return $this->classDependencies;
    }

    $this->parse();
    if (!$this->parsedClass) {
      return [];
    }

    // Get lists of interface, class, and trait dependencies.
    $interfaces = [];
    foreach ($this->parsedClass->implements as $interface) {
      $interfaces[] = (string) $interface;
    }
    $extends = [];
    if ($this->parsedClass->extends) {
      $extends[] = (string) $this->parsedClass->extends;
    }
    $traits = [];
    foreach ($this->parsedClass->getTraitUses() as $trait_use) {
      foreach ($trait_use->traits as $trait) {
        $traits[] = (string) $trait;
      }
      foreach ($trait_use->adaptations as $adaptation) {
        if ($adaptation->trait) {
          $traits[] = (string) $adaptation->trait;
        }
      }
    }

    $this->classDependencies = [
      'class' => $extends,
      'interface' => $interfaces,
      'trait' => $traits,
    ];
    return $this->classDependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function hasMissingDependencies(array $dependencies): bool {
    foreach ($dependencies as $type => $names) {
      if (!in_array($type, ['class', 'interface', 'trait'])) {
        continue;
      }
      foreach ($names as $name) {
        // Doing *_exists() checks here on the identified dependencies does run
        // a risk that additional dependencies in the hierarchies of the
        // dependencies are missing, resulting in an exception or fatal error.
        // The rationale behind running these checks is that that risk is lower
        // than the risk of the identified dependencies themselves being missing
        // and causing a thrown exception or fatal error on reflection of the
        // class.
        try {
          if (!call_user_func($type . '_exists', $name)) {
            return TRUE;
          }
        }
        catch (\Error) {
          // Error exception thrown when there if the dependency extends a
          // missing class or implements a missing interface somewhere in its
          // hierarchy.
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Gets the PHP Parser object.
   *
   * @return \PhpParser\Parser
   *   The PHP Parser.
   */
  public static function getParser(): Parser {
    if (isset(static::$parser)) {
      return static::$parser;
    }

    static::$parser = (new ParserFactory())->createForHostVersion();
    return static::$parser;
  }

  /**
   * Gets the attribute objects parsed from the class.
   *
   * @return \PhpParser\Node\Attribute[]
   *   The parsed attributes.
   */
  protected function getClassAttributes(): array {
    if (isset($this->classAttributes)) {
      return $this->classAttributes;
    }

    $this->parse();
    if (!$this->parsedClass) {
      $this->classAttributes = [];
      return $this->classAttributes;
    }

    $this->classAttributes = (new NodeFinder())->findInstanceOf($this->parsedClass->attrGroups, Attribute::class);
    return $this->classAttributes;
  }

}
