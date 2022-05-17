<?php

namespace Drupal\cookie_content_blocker;

/**
 * Interface BlockedLibraryManagerInterface.
 *
 * @package Drupal\cookie_content_blocker
 */
interface BlockedLibraryManagerInterface {

  /**
   * Add a library to the list of blocked libraries.
   *
   * @param string $library
   *   The name of the library.
   */
  public function addBlockedLibrary(string $library): void;

  /**
   * Get a list of blocked libraries.
   *
   * @return string[]
   *   The list of blocked libraries.
   */
  public function getBlockedLibraries(): array;

  /**
   * Check whether there are any blocked libraries.
   *
   * @return bool
   *   TRUE is there is at least one blocked library, FALSE otherwise.
   */
  public function hasBlockedLibraries(): bool;

  /**
   * Check whether a single library is blocked.
   *
   * @param string $library
   *   The name of the library.
   *
   * @return bool
   *   TRUE if the library is blocked, FALSE otherwise.
   */
  public function isBlocked(string $library): bool;

}
