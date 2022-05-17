<?php

namespace Drupal\cookie_content_blocker\ElementProcessor;

use Drupal\cookie_content_blocker\ElementProcessorInterface;

/**
 * Class ElementProcessor.
 *
 * @package Drupal\cookie_content_blocker\ElementProcessor
 */
class ElementProcessor extends ElementProcessorBase {

  /**
   * The processor list.
   *
   * @var \Drupal\cookie_content_blocker\ElementProcessorInterface[]
   */
  protected $processors = [];

  /**
   * {@inheritdoc}
   */
  public function addProcessor(ElementProcessorInterface $processor): void {
    $this->processors[] = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function processElement(array $element): array {
    if (!$this->applies($element)) {
      return $element;
    }

    foreach ($this->processors as $processor) {
      if (!$processor->applies($element)) {
        continue;
      }

      $element = $processor->processElement($element);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $element): bool {
    return !empty($element['#cookie_content_blocker']);
  }

}
