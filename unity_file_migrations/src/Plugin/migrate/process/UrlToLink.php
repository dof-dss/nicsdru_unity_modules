<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'D7 Url to D9 Link' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "url_to_link"
 * )
 */
class UrlToLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
  {
    // Copy the 'value' and 'title' elements from Drupal 7 to the
    // corresponding 'uri' and 'title' elements in Drupal 9.
    $link = [];
    $link['uri'] = $value['value'];
    $link['title'] = $value['title'];

    return $link;
  }

}
