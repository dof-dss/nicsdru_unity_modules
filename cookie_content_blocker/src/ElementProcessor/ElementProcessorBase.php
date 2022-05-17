<?php

namespace Drupal\cookie_content_blocker\ElementProcessor;

use Drupal\cookie_content_blocker\ElementProcessorInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class ElementProcessorBase.
 *
 * A base for Cookie content blocker element processors.
 *
 * @package Drupal\cookie_content_blocker\ElementProcessor
 */
abstract class ElementProcessorBase implements ElementProcessorInterface, TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  abstract public function applies(array $element): bool;

  /**
   * {@inheritdoc}
   */
  abstract public function processElement(array $element): array;

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['processElement'];
  }

}
