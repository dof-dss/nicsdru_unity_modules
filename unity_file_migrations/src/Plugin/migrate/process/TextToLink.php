<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'D7 text field to D9 Link' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "text_to_link"
 * )
 */
class TextToLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Copy the 'value' element from Drupal 7 to the
    // corresponding 'uri' element in Drupal 9 adding the https:// protocol.
    $link = [];
    // Some text values have a protocol, if so keep the protocol.
    if (preg_match('(http(s)?:\/\/)', $value['value'], $matches)) {
      $link['uri'] = $value['value'];
    }
    else {
      $link['uri'] = 'https://' . $value['value'];
    }

    return $link;
  }

}
