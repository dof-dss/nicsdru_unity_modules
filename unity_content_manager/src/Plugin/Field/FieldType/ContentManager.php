<?php

namespace Drupal\unity_content_manager\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'content manager' field type.
 */
#[FieldType(
  id: "content_manager",
  label: new TranslatableMarkup("Content manager link"),
  description: new TranslatableMarkup("Stores a URL string with URL text."),
  default_widget: "content_manager_widget",
  default_formatter: "content_manager_formatter",
  constraints: [
    "ContentManagerURL" => [],
    "ContentManagerText" => [],
  ]
)]
class ContentManager extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Link URL'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Link text'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'url' => [
          'description' => 'The URL of the link.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'title' => [
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }

}
