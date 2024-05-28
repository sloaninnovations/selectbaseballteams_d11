<?php

namespace Drupal\Core\Template;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Theme\ComponentPluginManager;
use Twig\Extension\AbstractExtension;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;
use Twig\TwigFunction;

/**
 * The embed parser so we can be aware of variants.
 *
 * Based on EmbedTokenParser. As its final and not considered API we cannot extend it.
 *
 * @internal
 */
final class ComponentsEmbedParser extends IncludeTokenParser
{
  public function parse(Token $token): Node
  {
    $stream = $this->parser->getStream();

    $parent = $this->parser->getExpressionParser()->parseExpression();

    [$variables, $only, $ignoreMissing] = $this->parseArguments();
    $parentToken = $fakeParentToken = new Token(/* Token::STRING_TYPE */ 7, '__parent__', $token->getLine());
    if ($parent instanceof ConstantExpression) {
      $parentToken = new Token(/* Token::STRING_TYPE */ 7, $parent->getAttribute('value'), $token->getLine());
    } elseif ($parent instanceof NameExpression) {
      $parentToken = new Token(/* Token::NAME_TYPE */ 5, $parent->getAttribute('name'), $token->getLine());
    }

    // inject a fake parent to make the parent() function work
    $stream->injectTokens([
      new Token(/* Token::BLOCK_START_TYPE */ 1, '', $token->getLine()),
      new Token(/* Token::NAME_TYPE */ 5, 'extends', $token->getLine()),
      $parentToken,
      new Token(/* Token::BLOCK_END_TYPE */ 3, '', $token->getLine()),
    ]);

    $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], true);

    // override the parent with the correct one
    if ($fakeParentToken === $parentToken) {
      $module->setNode('parent', $parent);
    }
    $variant = $this->getVariantFromVariables($variables);

    $this->parser->embedTemplate($module);

    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    $templateName = $module->getTemplateName();
    return new EmbedNode($templateName, $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
  }

  public function decideBlockEnd(Token $token): bool
  {
    return $token->test('endembed');
  }

  public function getTag(): string
  {
    return 'embed';
  }

  protected function getVariantFromVariables(ArrayExpression $variables) {
    $variant = '';
    $foundVariant = FALSE;
    $keyPairs = $variables->getKeyValuePairs();

    foreach ($keyPairs as $keyPair) {
      if ($keyPair['key']->getAttribute('value') == 'variant') {
        $variant = $keyPair['value']->getAttribute('value');
        break;
      }
    }
    return $variant;
  }

  protected function getVariant(ModuleNode $node) {
    assert ($node instanceof ModuleNode);
    $variant = '';
    $foundVariant = false;
    foreach ($node->getNode('body')->getIterator() as $childNode) {
      if ($foundVariant && $childNode instanceof ConstantExpression && $childNode->hasAttribute('value')) {
        $variant = $childNode->getAttribute('value');
        break;
      }
      if ($childNode instanceof ConstantExpression && $childNode->hasAttribute('value') && $childNode->getAttribute('value') === 'variant') {
        $foundVariant = true;
      }
    }

    return $variant;
  }
}
