<?php

namespace Drupal\cookie_content_blocker;

/**
 * Interface ElementProcessorInterface.
 *
 * @package Drupal\cookie_content_blocker
 */
interface ElementProcessorInterface {

  /**
   * Declares if the route processor applies to the given element.
   *
   * @param array $element
   *   The element to process.
   *
   * @return bool
   *   TRUE if the check applies, FALSE otherwise.
   */
  public function applies(array $element): bool;

  /**
   * Processes the element.
   *
   * @param array $element
   *   The element to process.
   *
   * @return array
   *   The processed element.
   */
  public function processElement(array $element): array;

}
