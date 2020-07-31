<?php

// namespace Drupal\asu_migrate\Plugin\migrate\process;

// use Drupal\migrate\ProcessPluginBase;
// use Drupal\migrate\MigrateException;
// use Drupal\migrate\MigrateExecutableInterface;
// use Drupal\migrate\Row;


// /**
//  * Convert a string and a key into an associative array.
//  *
//  * @MigrateProcessPlugin(
//  *   id = "parse_name_uri"
//  * )
//  */
// class ParseNameUri extends ProcessPluginBase {

//   /**
//    * {@inheritdoc}
//    */
//   public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
//     if (!is_string($value)) {
//       throw new MigrateException('Plugin parse_name_uri requires a string input.');
//     }
//     if (!isset($this->configuration['keys'])) {
//       throw new MigrateException('Plugin parse_name_uri requires a keys.');
//     }
//     \Drupal::logger('parse name uri')->info(print_r($this->configuration, TRUE));
//     \Drupal::logger('parse name uri')->info($value);
//     $keys = $this->configuration['keys'];
//     $vals = explode($this->configuration['separator'], $value);
//     $newarr = [];
//     foreach ($vals as $k => $v) {
//       $newarr[$keys[$k]] = str_replace($this->configuration['remove'], '', $v);
//     }
//     \Drupal::logger('parse name uri')->info(print_r($newarr, TRUE));

//     return $newarr;
//   }

// }
