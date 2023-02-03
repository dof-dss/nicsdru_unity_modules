<?php

namespace Drupal\unity_map_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block for containing a map.
 *
 * This block contains one field which holds a map, it may
 * be placed in any region and then restricted to certain pages
 * using the usual block criteria.
 *
 * @Block(
 *  id = "map_block",
 *  admin_label = @Translation("Unity Map block"),
 * )
 */
class MapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello from a new block'),
    ];
  }

}
