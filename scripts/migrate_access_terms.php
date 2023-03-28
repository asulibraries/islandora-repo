<?php

use Drupal\taxonomy\Entity\Term;
$terms = [
  [
    'vid'=>'islandora_access',
    'name'=>'ASU Only'
  ],
  [
    'vid'=>'islandora_access',
    'name'=>'Private'
  ],
  [
    'vid'=>'islandora_access',
    'name'=>'Public'
  ]
];
$tm = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($terms as $t) {
  if(count($tm->loadByProperties($t)) < 1) {
    $term = Term::create($t);
    $term->enforceIsNew();
    $term->save();
  }
}
