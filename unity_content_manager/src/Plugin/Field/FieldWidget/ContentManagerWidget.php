<?php

namespace Drupal\unity_content_manager\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'content manager link' widget.
 */
#[FieldWidget(
  id: 'content_manager_widget',
  label: new TranslatableMarkup('Content Manager'),
  field_types: ['content_manager'],
)]
class ContentManagerWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content manager URL'),
      '#default_value' => $items[$delta]->url ?? NULL,
      '#maxlength' => 255,
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content manager link text'),
      '#default_value' => $items[$delta]->title ?? NULL,
      '#maxlength' => 255,
    ];

    return $element;
  }

}
