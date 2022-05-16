<?php

namespace Drupal\cookie_content_blocker\ElementProcessor;

use Drupal\cookie_content_blocker\BlockedLibraryManagerInterface;

/**
 * Class AttachedProcessor.
 *
 * Processes attachments on blocked elements.
 *
 * @package Drupal\cookie_content_blocker\ElementProcessor
 */
class AttachedProcessor extends ElementProcessorBase {

  /**
   * The library manager.
   *
   * @var \Drupal\cookie_content_blocker\BlockedLibraryManagerInterface
   */
  protected $libraryManager;

  /**
   * Constructs a AttachedProcessor object.
   *
   * @param \Drupal\cookie_content_blocker\BlockedLibraryManagerInterface $library_manager
   *   The library manager for blocked libraries.
   */
  public function __construct(BlockedLibraryManagerInterface $library_manager) {
    $this->libraryManager = $library_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $element): bool {
    return !empty($element['#attached']['library']);
  }

  /**
   * {@inheritdoc}
   */
  public function processElement(array $element): array {
    foreach ($element['#attached']['library'] as $library) {
      $this->libraryManager->addBlockedLibrary($library);
    }
    return $element;
  }

}
