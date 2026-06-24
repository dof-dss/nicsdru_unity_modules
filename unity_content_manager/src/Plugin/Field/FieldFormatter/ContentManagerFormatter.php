<?php

namespace Drupal\unity_content_manager\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'default' formatter.
 */
#[FieldFormatter(
  id: 'content_manager_formatter',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'content_manager',
  ],
)]
class ContentManagerFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      $url = $item->url;
      // @phpstan-ignore-next-line
      $url_text = $item->title;

      $element[$delta] = [
        '#type' => 'link',
        '#title' => $url_text,
        '#url' => [
          'url' => $url,
          'link_text' => $url_text,
        ]
      ];
    }

    return $element;
  }

}
