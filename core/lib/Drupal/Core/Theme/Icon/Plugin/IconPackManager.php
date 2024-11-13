<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\IconExtractorPluginManager;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Defines an Icon Pack plugin manager to deal with icons.
 *
 * Extension can define icon pack in an EXTENSION_NAME.icons.yml file
 * contained in the extension's base directory. Each icon pack must have
 * `extractor` and `template`, the `config` value can be required based on the
 * `extractor` value:
 * @code
 * MACHINE_NAME:
 *   extractor: (string) EXTRACTOR_PLUGIN_ID, core: path, svg, svg_sprite
 *   template: (string) Twig template to render the icon, icon values
 *     available in the template:
 *       icon_id: Icon ID based on filename or {icon_id} pattern.
 *       source: Icon path or url resolved (relative to extension or Drupal).
 *       ... all specific values from extractor plugin.
 *       ... all `settings` values if set below.
 *   config:
 *     sources: (array) Mandatory for extractors: path, svg, svg_sprite
 *       - path/to/relative/*.svg
 *       - path/to/relative/{icon_id}-suffix.svg # Extract icon id.
 *       - /path/relative/drupal/web/*.svg
 *       - http://www.my_domain.com/my_icon.png
 *       - ...
 *     # ... Other keys for specific extractor plugins.
 *   # Recommended values:
 *   label: (string) The name of the Icon pack for display
 *   # Optional values:
 *   description: (string)
 *   license:
 *     name: (string) A System Package Data Exchange (SPDX) license identifier
 *       such as "GPL-2.0-or-later" (see https://spdx.org/licenses/), or if
 *       not applicable, the human-readable name of the license.
 *     url: (string) The URL of the license file/information for the version
 *       of the library used.
 *     gpl-compatible: A Boolean for whether this library is GPL compatible.
 *   links: (array)
 *     - (string) The URL of a Documentation page
 *     - ...
 *   version: (string) The version of the pack.
 *   enabled: (boolean) A boolean, default to TRUE, set FALSE to disable.
 *   preview: (string) Twig template for preview on admin backend with contrib
 *     modules for Field API, CKEditor... used when template is not an <img>
 *     or <svg> tag. For example a class based or font based icon pack. This
 *     is then needed to ensure a standard display of the icon size. This
 *     template should match a square 48x48px display of the icon.
 *   library: (string) Drupal library machine name to include.
 *   # Optional values for the template, they must follow JSON Schema. Only
 *   # non scalar primitive.
 *   # A specific class \Drupal\Core\Theme\Icon\IconExtractorSettingsForm
 *   # will transform these settings in Drupal Form API to be available for
 *   # changes in FormElement and contrib modules implementing Field API, Menu,
 *   # CKEditor...
 *   # Constraints in the form will not apply on the value passed to the
 *   # template, only when a FormElement is used they will be enforced.
 *   settings: (array)
 *     FORM_KEY: (string) Name of the setting in the template.
 *       title : (string) Title of the setting.
 *       description : (string) Optional description of the setting.
 *       type : (string) Primitive type: string, number, integer, boolean.
 *       default: (mixed) Form default value, will not be used as default
 *         value in the template, template must use |default() twig filter.
 *       [...] Specific JSON Schema values like multipleOf, minimum, maximum...
 * @endcode
 * For example:
 * @code
 * my_icon_pack:
 *   label: "My icons"
 *   description: "My UI Icons pack to use everywhere."
 *   license:
 *     name: GPL3-or-later
 *     url: https://www.gnu.org/licenses/gpl-3.0.html
 *     gpl-compatible: true
 *   links:
 *     - https://my_doc
 *   version: 1.0.0
 *   enabled: true
 *   extractor: svg
 *   config:
 *     sources:
 *       - icons/{icon_id}.svg
 *       - icons_grouped/{group}/{icon_id}.svg
 *   settings:
 *     size:
 *       title: "Size"
 *       type: "integer"
 *       minimum: 24
 *       default: 32
 *   template: >
 *     <img src={{ source }} width="{{ size|default(32) }}" height="{{ size|default(32) }}"/>
 *   library: "my_theme/my_lib"
 * @endcode
 *
 * @see \Drupal\Core\Theme\Icon\IconExtractorInterface
 * @see \Drupal\Core\Theme\Icon\IconExtractorWithFinderInterface
 * @see \Drupal\Core\Theme\Icon\IconExtractorSettingsForm
 * @see plugin_api
 *
 * @internal
 *   Icon is currently experimental and should only be leveraged by experimental
 *   modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class IconPackManager extends DefaultPluginManager implements IconPackManagerInterface {

  private const SCHEMA_VALIDATE = 'core/assets/schemas/v1/icon_pack.schema.json';

  /**
   * Lookup array for icons by id for faster getIcon() access.
   *
   * Icons are not indexed by ID to simplify the Extractor plugin as much as
   * possible. So we need to build a lookup table to speed up the lookup.
   *
   * @var array<string, \Drupal\Core\Theme\Icon\IconDefinitionInterface>
   */
  private array $iconLookup = [];

  /**
   * Whether the icon lookup has been built.
   *
   * @var bool
   */
  private bool $isLookupBuilt = FALSE;

  /**
   * The schema validator.
   *
   * This property will only be set if the validator library is available.
   *
   * @var \JsonSchema\Validator|null
   */
  private ?Validator $validator = NULL;

  /**
   * Constructs the IconPackPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Theme\Icon\IconExtractorPluginManager $iconPackExtractorManager
   *   The icon plugin extractor service.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    protected IconExtractorPluginManager $iconPackExtractorManager,
    protected string $appRoot,
  ) {
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('icon_pack');
    $this->setCacheBackend($cacheBackend, 'icon_pack', ['icon_pack_plugin']);
  }

  /**
   * Sets the validator service if available.
   *
   * @param \JsonSchema\Validator|null $validator
   *   The JSON Validator class.
   */
  public function setValidator(?Validator $validator = NULL): void {
    if ($validator) {
      $this->validator = $validator;
      return;
    }
    if (class_exists(Validator::class)) {
      $this->validator = new Validator();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id): void {
    if (preg_match('@[^a-z0-9_]@', $plugin_id)) {
      throw new IconPackConfigErrorException(sprintf('Invalid Icon Pack id in: %s, name: %s must contain only lowercase letters, numbers, and underscores.', $definition['provider'], $plugin_id));
    }

    $this->validateDefinition($definition);

    // Do not include disabled definition with `enabled: false`.
    if (!($definition['enabled'] ?? TRUE)) {
      return;
    }

    if (!isset($definition['provider'])) {
      return;
    }

    // Provide path information for extractors.
    $relative_path = $this->moduleHandler->moduleExists($definition['provider'])
      ? $this->moduleHandler->getModule($definition['provider'])->getPath()
      : $this->themeHandler->getTheme($definition['provider'])->getPath();

    $definition['relative_path'] = $relative_path;
    // To avoid the need for appRoot in extractors.
    $definition['absolute_path'] = sprintf('%s/%s', $this->appRoot, $relative_path);

    // Load all discovered icons in the definition so they are cached.
    $definition['icons'] = $this->getIconsFromDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(?array $allowed_icon_pack = NULL): array {
    $definitions = $this->getDefinitions();

    if (NULL === $definitions) {
      return [];
    }

    $icons = [];
    foreach ($definitions as $definition) {
      if ($allowed_icon_pack && !in_array($definition['id'], $allowed_icon_pack, TRUE)) {
        continue;
      }
      $icons = array_merge($icons, $definition['icons'] ?? []);
    }

    // Build the icon lookup array for faster access.
    $this->iconLookup = array_reduce($icons, function ($carry, $icon) {
      if ($icon instanceof IconDefinitionInterface) {
        $carry[$icon->getId()] = $icon;
      }
      return $carry;
    }, []);

    $this->isLookupBuilt = TRUE;

    return $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(string $icon_id): ?IconDefinitionInterface {
    if (!$this->isLookupBuilt) {
      $this->getIcons();
    }

    return $this->iconLookup[$icon_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorFormDefaults(string $pack_id): array {
    $all_icon_pack = $this->getDefinitions();

    if (!isset($all_icon_pack[$pack_id]) || !isset($all_icon_pack[$pack_id]['settings'])) {
      return [];
    }

    $default = [];
    foreach ($all_icon_pack[$pack_id]['settings'] as $name => $definition) {
      if (isset($definition['default'])) {
        $default[$name] = $definition['default'];
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorPluginForms(array &$form, FormStateInterface $form_state, array $default_settings = [], array $allowed_icon_packs = [], bool $wrap_details = FALSE): void {
    $icon_pack = $this->getDefinitions();

    if (NULL === $icon_pack) {
      return;
    }

    if (!empty($allowed_icon_packs)) {
      $icon_pack = array_intersect_key($icon_pack, $allowed_icon_packs);
    }

    $extractor_forms = $this->iconPackExtractorManager->getExtractorForms($icon_pack);
    if (empty($extractor_forms)) {
      return;
    }

    foreach ($icon_pack as $pack_id => $plugin) {
      // Simply skip if no settings declared in definition.
      if (count($plugin['settings'] ?? []) === 0) {
        continue;
      }

      // Create the container for each extractor settings used to have the
      // extractor form.
      $form[$pack_id] = [
        '#type' => $wrap_details ? 'details' : 'container',
        '#title' => $wrap_details ? $plugin['label'] : $pack_id,
      ];

      // Create the extractor form and set settings so we can build with values.
      $subform_state = SubformState::createForSubform($form[$pack_id], $form, $form_state);
      $subform_state->getCompleteFormState()->setValue('saved_values', $default_settings[$pack_id] ?? []);
      if (is_a($extractor_forms[$pack_id], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $form[$pack_id] += $extractor_forms[$pack_id]->buildConfigurationForm($form[$pack_id], $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function listIconPackOptions(bool $include_description = FALSE): array {
    $definitions = $this->getDefinitions();

    if (NULL === $definitions) {
      return [];
    }

    $options = [];
    foreach ($definitions as $definition) {
      if (empty($definition['icons'])) {
        continue;
      }
      $label = $definition['label'] ?? $definition['id'];
      if ($include_description && isset($definition['description'])) {
        $label = sprintf('%s - %s', $label, $definition['description']);
      }
      $options[$definition['id']] = sprintf('%s (%u)', $label, count($definition['icons']));
    }

    natsort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $this->discovery = new YamlDiscovery('icons', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery
        ->addTranslatableProperty('label')
        ->addTranslatableProperty('description');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists(mixed $provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Discover list of icons from definition extractor.
   *
   * @param array $definition
   *   The definition.
   *
   * @return array
   *   Discovered icons.
   */
  private function getIconsFromDefinition(array $definition): array {
    if (!isset($definition['extractor'])) {
      return [];
    }

    /** @var \Drupal\Core\Theme\Icon\IconExtractorInterface $extractor */
    $extractor = $this->iconPackExtractorManager->createInstance($definition['extractor'], $definition);
    return $extractor->discoverIcons();
  }

  /**
   * Validates a definition against the JSON schema specification.
   *
   * @param array $definition
   *   The definition to alter.
   *
   * @return bool
   *   FALSE if the response failed validation, otherwise TRUE.
   *
   * @throws \Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException
   *   Thrown when the definition is not valid.
   */
  private function validateDefinition(array $definition): bool {
    // If the validator isn't set, then the validation library is not installed.
    if (!$this->validator) {
      return TRUE;
    }

    $schema_ref = sprintf(
      'file://%s/%s',
      $this->appRoot,
      self::SCHEMA_VALIDATE
    );
    $schema = (object) ['$ref' => $schema_ref];

    $definition_object = Validator::arrayToObjectRecursive($definition);

    $this->validator->validate($definition_object, $schema, Constraint::CHECK_MODE_COERCE_TYPES);

    if ($this->validator->isValid()) {
      return TRUE;
    }

    $message_parts = array_map(
      static fn (array $error): string => sprintf("[%s] %s", $error['property'], $error['message']),
      $this->validator->getErrors()
    );
    $message = implode(", ", $message_parts);

    throw new IconPackConfigErrorException(
      sprintf(
        '%s:%s Error in definition `%s`:%s',
        $definition['provider'],
        $definition['id'],
        $definition_object->id,
        $message
      )
    );
  }

}
