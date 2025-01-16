<?php

namespace Drupal\link\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'link' field type.
 */
#[FieldType(
  id: "link",
  label: new TranslatableMarkup("Link"),
  description: new TranslatableMarkup("Stores a URL string, optional varchar link text, and optional blob of attributes to assemble a link."),
  default_widget: "link_default",
  default_formatter: "link",
  constraints: [
    "LinkType" => [],
    "LinkAccess" => [],
    "LinkExternalProtocols" => [],
    "LinkNotExistingInternal" => [],
  ]
)]
class LinkItem extends FieldItemBase implements LinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_GENERIC,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uri'] = DataDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('URI'));

    $properties['full_url'] = DataDefinition::create('link_url')
      ->setLabel(t('URL'))
      ->setDescription(t('The processed URL for this link that can e.g. be used in in href attributes.'))
      ->setComputed(TRUE)
      ->setInternal(FALSE)
      ->setReadOnly(TRUE);

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Link text'));

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('Options'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'uri' => [
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'title' => [
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'options' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'uri' => [['uri', 30]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allowed link type'),
      '#default_value' => $this->getSetting('link_type'),
      '#options' => [
        static::LINK_INTERNAL => $this->t('Internal links only'),
        static::LINK_EXTERNAL => $this->t('External links only'),
        static::LINK_GENERIC => $this->t('Both internal and external links'),
      ],
    ];

    $element['title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow link text'),
      '#default_value' => $this->getSetting('title'),
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    if ($field_definition->getItemDefinition()->getSetting('link_type') & LinkItemInterface::LINK_EXTERNAL) {
      // Set of possible top-level domains.
      $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
      // Set random length for the domain name.
      $domain_length = mt_rand(7, 15);

      switch ($field_definition->getSetting('title')) {
        case DRUPAL_DISABLED:
          $values['title'] = '';
          break;

        case DRUPAL_REQUIRED:
          $values['title'] = $random->sentences(4);
          break;

        case DRUPAL_OPTIONAL:
          // In case of optional title, randomize its generation.
          $values['title'] = mt_rand(0, 1) ? $random->sentences(4) : '';
          break;
      }
      $values['uri'] = 'https://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (count($tlds) - 1))];
    }
    else {
      $values['uri'] = 'base:' . $random->name(mt_rand(1, 64));
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uri')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getUrl()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'uri';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->uri, (array) $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the link item values can be kept in sync with computed
    // property url.
    if ($property_name === 'full_url') {
      $property = $this->get('full_url');
      if ($url = $property->getValue()) {
        $parsed = UrlHelper::parse($url);
        // If the path is not an external URL then add 'internal:' prefix to
        // make it a valid uri.
        if (strpos($parsed['path'], ':') === FALSE) {
          $parsed['path'] = 'internal:' . $parsed['path'];
        }
        $this->writePropertyValue('uri', $parsed['path']);
        // Only set the options if we have query parameters or a fragment.
        if (!empty($parsed['query']) || !empty($parsed['fragment'])) {
          $this->writePropertyValue('options', [
            'query' => $parsed['query'],
            'fragment' => $parsed['fragment'],
          ]);
        }
      }
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): ?string {
    return $this->title ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'options' => [],
      ];
    }
    parent::setValue($values, $notify);
    // Support setting the field item with only url property, but make sure
    // values stay in sync if only url property is passed.
    // NULL is a valid value, so we use array_key_exists().
    if (is_array($values) && array_key_exists('full_url', $values) && !array_key_exists('uri', $values)) {
      $this->onChange('full_url', FALSE);
    }
  }

}
