<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_video' formatter.
 */
#[FieldFormatter(
  id: 'file_video',
  label: new TranslatableMarkup('Video'),
  description: new TranslatableMarkup('Display the file using an HTML5 video tag.'),
  field_types: [
    'file',
  ],
)]
class FileVideoFormatter extends FileMediaFormatterBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'video';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'muted' => FALSE,
      'width' => 640,
      'height' => 480,
      'poster' => '',
      'poster_image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $fields = $this->entityFieldManager->getFieldDefinitions($form['#entity_type'], $form['#bundle']);

    // Get all image fields for html5 poster.
    $image_fields = [];
    foreach ($fields as $field) {
      if ($field->getType() == 'image') {
        $image_fields[$field->getName()] = $field->getLabel();
      }
    }
    $image_styles = image_style_options(FALSE);
    $image_styles_description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );

    return parent::settingsForm($form, $form_state) + [
      'muted' => [
        '#title' => $this->t('Muted'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('muted'),
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        // A width of zero pixels would make this video invisible.
        '#min' => 1,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        // A height of zero pixels would make this video invisible.
        '#min' => 1,
      ],
      'poster' => [
        '#type' => 'select',
        '#title' => $this->t('Poster field'),
        '#description' => $this->t('An image field to use as the source of the poster attribute'),
        '#default_value' => $this->getSetting('poster'),
        '#options' => $image_fields,
        '#empty_option' => $this->t('- None -'),
      ],
      'poster_image_style' => [
        '#type' => 'select',
        '#title' => $this->t('Poster field image style'),
        '#default_value' => $this->getSetting('poster_image_style'),
        '#options' => $image_styles,
        '#empty_option' => $this->t('None (original image)'),
        '#description' => $image_styles_description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles'),
        ],
        '#states' => [
          'visible' => [
            ':input[name$="[settings][poster]"]' => ['filled' => TRUE],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Muted: %muted', ['%muted' => $this->getSetting('muted') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Size: %width x %height pixels', [
      '%width' => $this->getSetting('width'),
      '%height' => $this->getSetting('height'),
    ]);
    if (!empty($this->getSetting('poster'))) {
      $summary[] = $this->t('Poster field: %poster', ['%poster' => $this->getSetting('poster')]);
      $summary[] = $this->t('Poster image style: %poster_image_style', ['%poster_image_style' => $this->getSetting('poster_image_style') ?: $this->t('None (original image)')]);
    }

    if ($width = $this->getSetting('width')) {
      $summary[] = $this->t('Width: %width pixels', [
        '%width' => $width,
      ]);
    }

    if ($height = $this->getSetting('height')) {
      $summary[] = $this->t('Height: %height pixels', [
        '%height' => $height,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $items->getEntity();

    if (!empty($this->getSetting('poster')) && !$entity->get($this->getSetting('poster'))->isEmpty()) {
      $poster_file_item = $entity->get($this->getSetting('poster'))[0];

      $poster_file_entity = $poster_file_item->entity;
      // Set the entity in the correct language for display.
      if ($poster_file_entity instanceof TranslatableInterface && $poster_file_entity->hasTranslation($langcode)) {
        $poster_file_entity = $poster_file_entity->getTranslation($langcode);
      }

      $poster_image_style_id = $this->getSetting('poster_image_style');
      $cache_tags = $elements[0]['#cache']['tags'] ?? [];
      if (!empty($poster_image_style_id)) {
        // With imagecache selected:
        $poster_image_style = $this->imageStyleStorage->load($poster_image_style_id);
        $cache_tags = Cache::mergeTags($cache_tags, $poster_image_style->getCacheTags());
        $poster_url = $poster_image_style->buildUrl($poster_file_entity->getFileUri());
      }
      else {
        // Without imagecache:
        $poster_url = $poster_file_entity->createFileUrl();
      }
      $cache_tags = Cache::mergeTags($cache_tags, $poster_file_entity->getCacheTags());

      $poster_attributes = new Attribute();
      $poster_attributes->setAttribute('poster', $poster_url);
      $elements[0]['#attributes']->merge($poster_attributes);
      $elements[0]['#cache']['tags'] = $cache_tags;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributes(array $additional_attributes = []) {
    $attributes = parent::prepareAttributes(['muted']);
    if (($width = $this->getSetting('width'))) {
      $attributes->setAttribute('width', $width);
    }
    if (($height = $this->getSetting('height'))) {
      $attributes->setAttribute('height', $height);
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('poster_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      // If this formatter uses a valid image style to display the image, add
      // the image style configuration entity as dependency of this formatter.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_id = $this->getSetting('poster_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
        // If a valid replacement has been provided in the storage, replace the
        // image style with the replacement and signal that the formatter plugin
        // settings were updated.
        if (!empty($replacement_id) && ImageStyle::load($replacement_id)) {
          $this->setSetting('poster_image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

}
