<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the value is the name of an extension.
 */
#[Constraint(
  id: 'ExtensionExists',
  label: new TranslatableMarkup('Extension exists', [], ['context' => 'Validation'])
)]
class ExtensionExistsConstraint extends SymfonyConstraint {

  /**
   * The error message for a non-existent module.
   *
   * @var string
   */
  public string $moduleNotFoundMessage = "Module '@name' was not found.";

  /**
   * The error message for a existent but not installed module.
   *
   * @var string
   */
  public string $moduleNotInstalledMessage = "Module '@name' is not installed.";

  /**
   * The error message for a non-existent theme.
   *
   * @var string
   */
  public string $themeNotFoundMessage = "Theme '@name' was not found.";

  /**
   * The error message for a existent but not installed theme.
   *
   * @var string
   */
  public string $themeNotInstalledMessage = "Theme '@name' is not installed.";

  /**
   * The type of extension to look for. Can be 'module' or 'theme'.
   *
   * @var string
   */
  public string $type;

  /**
   * Whether the extension must be installed.
   *
   * @var bool
   */
  public bool $mustBeInstalled = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'type';
  }

}
