<?php

namespace Drupal\cookie_content_blocker;

use function in_array;

/**
 * Manages libraries that are blocked until consent is given.
 *
 * @package Drupal\cookie_content_blocker
 */
class BlockedLibraryManager implements BlockedLibraryManagerInterface {

  /**
   * The list of blocked libraries.
   *
   * @var string[]
   */
  protected $blockedLibraries = [];

  /**
   * {@inheritdoc}
   */
  public function addBlockedLibrary(string $library): void {
    $this->blockedLibraries[$library] = $library;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockedLibraries(): array {
    return $this->blockedLibraries;
  }

  /**
   * {@inheritdoc}
   */
  public function hasBlockedLibraries(): bool {
    return !empty($this->blockedLibraries);
  }

  /**
   * {@inheritdoc}
   */
  public function isBlocked(string $library): bool {
    return in_array($library, $this->blockedLibraries, TRUE);
  }

}
