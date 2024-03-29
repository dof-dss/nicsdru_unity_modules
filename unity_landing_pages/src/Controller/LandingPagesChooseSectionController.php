<?php

namespace Drupal\unity_landing_pages\Controller;

use Drupal\layout_builder\Controller\ChooseSectionController;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Controller to alter display of Layout builder Sections form.
 */
// @phpstan-ignore-next-line
class LandingPagesChooseSectionController extends ChooseSectionController {

  /**
   * Choose a layout plugin to add as a section.
   *
   * Improves upon the core layout builder display by adding additional
   * styling for layouts and the back link.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   *
   * @return array
   *   The render array.
   */
  public function build(SectionStorageInterface $section_storage, $delta) {
    $build = parent::build($section_storage, $delta);

    foreach ($build['layouts']['#items'] as &$item) {
      $item['#attributes']['class'][] = 'unity-landing-pages--add-section';
    }

    // Add sidebar title.
    $build['#title'] = $this->t('Select a layout');

    $build['layouts']['#attributes']['class'][] = 'unity-landing-pages';

    $build['#attached']['library'][] = 'unity_landing_pages/landing_page_admin';

    return $build;
  }

}
