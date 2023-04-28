<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'Url to Link' migrate process plugin.
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
    $link = [];
    $link['uri'] = $value['value'];
    $link['title'] = $value['title'];

    return $link;
  }

}
