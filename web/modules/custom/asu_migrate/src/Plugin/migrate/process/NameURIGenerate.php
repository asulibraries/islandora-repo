<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "name_uri_generate"
 * )
 */
class NameURIGenerate extends NameURILookup {
  protected $authority_uris = [
    'fast' => '|https?://id.worldcat.org/fast/.+|',
    'lctgm' => '|https?://www.loc.gov/pictures/item/tgm.+|',
    'naf' => '|https?://id.loc.gov/authorities/names/.+|',
    'naf' => '|https?://lccn.loc.gov/n.+|',
    'lcsh' => '|https?://id.loc.gov/authorities/subjects/.+|',
    'lcnaf' => '|https?://id.loc.gov/authorities/names/.+|',
  ];
  /**
   * {@inheritdoc}
   */
  public function transform($name_uri_pair, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uri_field = $this->configuration['uri_field'];
    $tid = parent::transform($name_uri_pair, $migrate_executable, $row, $destination_property);
    $default_vocabulary = $this->configuration['default_vocabulary'];
    if (!empty($tid)) {
      dsm($tid);
      return $tid;
    }
    elseif (!empty($default_vocabulary)) {
      $term_array = [
        'name' => $this->name,
        'vid'  => $default_vocabulary,
      ];
      if (!empty($this->uri)) {
        $source = 'other';
        foreach ($this->authority_uris as $code => $pattern) {
        //   \Drupal::logger('name uri generate')->info($pattern);
          $matches = NULL;
          $return = preg_match($pattern, $this->uri, $matches);
          if (count($matches) > 0) {
            $source = $code;
            break;
          }
        //   else {
        //     \Drupal::logger('name uri generate')->info("no pregmatch on " . $this->uri);
        //   }
        }
        $term_array[$uri_field] = [
          'uri' => $this->uri,
          'source' => $source,
        ];
      }
      $term = Term::create($term_array)->save();
      if (!empty($this->uri) && $tid = $this->getTidByURI($this->uri, $uri_field)) {
        $term = Term::load($tid);
      }
      elseif ($tid = $this->getTidByName($this->name)) {
        $term = Term::load($tid);
      }
      dsm($tid);
      return $tid;
    }
    return  0 ;
  }
}
