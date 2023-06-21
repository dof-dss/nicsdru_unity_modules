<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\Core\Link;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Class DocumentEmbed.
 *
 * Provides a 'DocumentEmbed' migrate process plugin.
 *
 * @category Document_Migrate
 * @package Drupalunity_File_MigrationsPluginmigrateprocess
 * @author Chris Clarke <Christopher.Clarke@finance-ni.gov.uk>
 *
 * @MigrateProcessPlugin(
 *  id = "document_embed"
 * )
 */
class DocumentEmbed extends ProcessPluginBase {

  /**
   * Transform Functionality.
   */
  public function transform(
        $value,
        MigrateExecutableInterface $migrate_executable,
        Row $row,
  $destination_property
    ) {

    // Create REGEX string to match file links.
    $embed_regex = '/<a[\w\s\.]*href="([\w:\-\/\.]*)(pdf|doc|docx)[\w\s\.\=\-"\':><(&);\/]+<\/a>/U';

    // Search for matches in body value.
    $matches = [];
    preg_match_all($embed_regex, $value['value'], $matches, PREG_SET_ORDER);

    // Iterate these matches to find publications ( if exists ).
    if (!empty($matches)) {

      foreach ($matches as $match) {

        // Validation to check the preg match returned array result.
        if (empty($match[0]) || empty($match[1])) {
          continue;
        }

        $query = \Drupal::entityQuery('node')
          ->condition('type', 'publication_page')
          ->condition('body', basename($match[1], "."), 'CONTAINS');

        $nids = $query->execute();

        // Node has returned with file included. Creating link to specific node.
        if ($lastNode = end($nids)) {
          // Publication Page link used for replacing embedded links.
          $publications_link = Link::createFromRoute(
                'Specific Publication Page',
                'entity.node.canonical',
                ['node' => $lastNode],
                [
                  'attributes' => [
                    'rel' => 'nofollow',
                    'class' => 'publication_link',
                  ],
                ])->toString();
        }
        else {
          // Else, just create route to default publication page
          // Publication Page link used for replacing embedded links.
          // Currently hardcoded NID but will be dynamic to prevent ID issues.
          $publications_link = Link::createFromRoute(
                'Default Publication Page',
                'entity.node.canonical',
                ['node' => 2593],
                [
                  'attributes' => [
                    'rel' => 'nofollow',
                    'class' => 'publication_link',
                  ],
                ])->toString();
        }

        // Replace links to publication (or publication page if not exists).
        // $value = preg_replace($embed_regex, $publications_link, $value);.
        // Str replace for multiple matches within body value.
        $value = str_replace($match[0], $publications_link, $value);
      }
    }

    return $value;
  }

}
