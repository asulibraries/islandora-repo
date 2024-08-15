<?php

/**
 * @file
 * Etdload.
 *
 * Loads ETDs delivered by ProQuest.
 * Run using Drush:
 * `drush scr scripts/etdload.php -- <dir for proquest zips> <collection nid>`
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\paragraphs\Entity\Paragraph;

$ns = \Drupal::entityTypeManager()->getStorage('node');
$ts = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

/**
 * Either finds or creates a term given the passed properties.
 */
function load_or_create_term(array $properties) {
  $ts = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $ts->loadByProperties($properties);
  if (!empty($terms)) {
    return reset($terms)->id();
  }
  \Drupal::logger('etdload')->info('Creating term: ' . json_encode($properties));
  $term = $ts->create($properties);
  $term->save();
  return $term->id();
}

/**
 * Create Media.
 *
 * Copies the file at the provided path to the c7 directory
 * and creates a corresponding media before returning it.
 */
function create_media(string $path, string $name = '') {
  $filename = basename($path);
  if (empty($name)) {
    $name = $filename;
  }
  // Determine which media to use.
  $ext = pathinfo($path, PATHINFO_EXTENSION);
  // Default to a File.
  $model = 'file';
  // @todo grab supported extensions for each media instead of hard-coded lists.
  // Document.
  if (in_array($ext, [
    'txt', 'rtf', 'doc', 'docx', 'ppt', 'pptx', 'xls',
    'xlsx', 'pdf', 'odf', 'odg', 'odp', 'ods', 'odt',
    'fodt', 'fods', 'fodp', 'fodg', 'key', 'numbers', 'pages',
  ])) {
    $model = 'document';
  }
  // Image.
  elseif (in_array($ext, ['png', 'gif', 'jpg', 'jpeg', 'svg'])) {
    $model = 'image';
  }
  // Audio.
  elseif (in_array($ext, ['mp3', 'wav', 'aac', 'aif', 'aiff', 'mid', 'flac', 'm4a', 'mp4'])) {
    $model = 'audio';
  }
  // Video.
  elseif (in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'dpx'])) {
    $model = 'video';
  }

  $new_name = preg_replace('/[ ,()&\[\]#]+/', '_', $filename);
  $destination = "private://c7";
  if (!\Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
    \Drupal::logger('etdload')->error("Can't write to $destination\n");
  }
  $file = \Drupal::service('file.repository')->writeData(file_get_contents($path), "$destination/$new_name", FileSystemInterface::EXISTS_REPLACE);
  $media = \Drupal::entityTypeManager()->getStorage('media')->create([
    'bundle' => $model,
    'name' => $name,
  ]);
  $media->set($media->getSource()->getConfiguration()['source_field'], ['target_id' => $file->id()])->setPublished(TRUE)->save();

  return $media;
}

$path = $extra[0];

if (!is_dir($path) || !is_writable($path)) {
  $this - io()->error("The path {$path} is either not a directory or not writable.");
  die("The path {$path} is either not a directory or not writable.");
}

$export_dir = implode(DIRECTORY_SEPARATOR, [$path, 'processed', date('Y-m-d_H-i-s')]);
if (!is_dir($export_dir)) {
  mkdir($export_dir, 0777, TRUE);
}
$processed_zip_dir = implode(DIRECTORY_SEPARATOR, [$export_dir, 'zips_processed']);
if (!is_dir($processed_zip_dir)) {
  mkdir($processed_zip_dir, 0777, TRUE);
}

// Maps:
$degree_map = [
  "masters" => load_or_create_term(['name' => "Masters Thesis", 'vid' => 'genre']),
  "doctoral" => load_or_create_term(['name' => "Doctoral Dissertation", 'vid' => 'genre']),
];
$language_map = [
// English.
  "en" => load_or_create_term(['name' => "en", 'vid' => 'language']),
// Spanish.
  "es" => load_or_create_term(['name' => "es", 'vid' => 'language']),
// Chinese.
  "zh" => load_or_create_term(['name' => "zh", 'vid' => 'language']),
// French.
  "fr" => load_or_create_term(['name' => "fr", 'vid' => 'language']),
// German.
  "de" => load_or_create_term(['name' => "de", 'vid' => 'language']),
// Italian.
  "it" => load_or_create_term(['name' => "it", 'vid' => 'language']),
// Japanese.
  "ja" => load_or_create_term(['name' => "ja", 'vid' => 'language']),
// Korean.
  "ko" => load_or_create_term(['name' => "ko", 'vid' => 'language']),
// Portuguese.
  "pt" => load_or_create_term(['name' => "pt", 'vid' => 'language']),
// Russian.
  "ru" => load_or_create_term(['name' => "ru", 'vid' => 'language']),
];

// Load defaults.
// Collection we are populating w/ node ID passed from commandline.
// 149053.
$collection_nid = $extra[1];

// DateTimeFormat for embargo release date.
$embargo_field_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;

// Terms (to be replaced with actual term lookups when testing with Drupal.)
$copyright_term = reset($ts->loadByProperties(['name' => "In Copyright", 'vid' => 'copyright_statements']))->id();
$reuse_term = reset($ts->loadByProperties(['name' => 'All Rights Reserved', 'vid' => 'reuse_permissions']))->id();
$theses_term = reset($ts->loadByProperties(['name' => "Academic theses", 'vid' => 'genre']))->id();

foreach (array_filter(scandir($path), function ($value) {
  return str_ends_with($value, '.zip');
}) as $zip_name) {
  $etd_id = basename($zip_name, '.zip');

  $zip = new ZipArchive();
  $zip_path = $path . DIRECTORY_SEPARATOR . $zip_name;
  if ($zip->open($zip_path) !== TRUE) {
    $this->io()->error("Could not open {$zip_path}!");
    continue;
  }

  $extract_destination = $export_dir . DIRECTORY_SEPARATOR . $etd_id;

  $zip->extractTo($extract_destination);
  $zip->close();

  $xml = simplexml_load_file(current(glob($extract_destination . DIRECTORY_SEPARATOR . '*_DATA.xml')));
  if (!$xml) {
    $this->io()->error("Could not find _DATA.xml in $extract_destination!\n");
    continue;
  }
  // Load all the elements for each field.
  $node_metadata = ['type' => 'scholarly_work', 'field_member_of' => ['target_id' => $collection_nid]];

  $title = (string) current($xml->xpath('DISS_description/DISS_title'));
  $node_metadata['title'] = $title;
  $title_p = Paragraph::create([
    'type' => 'complex_title',
    'field_main_title' => $title,
  ]);
  $title_p->save();
  $node_metadata['field_title'][] = [
    'target_id' => $title_p->id(),
    'target_revision_id' => $title_p->getRevisionId(),
  ];

  $node_metadata['field_edtf_date_created'] = (string) current($xml->xpath('DISS_description/DISS_dates/DISS_comp_date'));
  $node_metadata['field_extent'] = (string) current($xml->xpath('DISS_description/@page_count')) . ' pages';
  $node_metadata['field_rich_description'] = '';
  foreach ($xml->xpath('DISS_content/DISS_abstract/DISS_para') as $para) {
    $node_metadata['field_rich_description'] .= "<p>$para</p>";
  }

  // Term reference fields.
  $node_metadata['field_language'] = [
    'target_id' => $language_map[(string) current($xml->xpath('DISS_description/DISS_categorization/DISS_language'))],
  ];
  $node_metadata['field_copyright_statement'] = ['target_id' => $copyright_term];
  $node_metadata['field_reuse_permissions'] = ['target_id' => $reuse_term];
  $degree_type = (string) current($xml->xpath('DISS_description/@type'));
  $node_metadata['field_genre'][] = [
    'target_id' => $degree_map[$degree_type],
  ];
  $node_metadata['field_genre'][] = ['target_id' => $theses_term];

  // Contributors.
  foreach ([
    'relators:aut' => 'DISS_authorship/DISS_author[@type="primary"]',
    'relators:ths' => 'DISS_description/DISS_advisor',
    'barrettrelators:dgc' => 'DISS_description/DISS_cmte_member',
  ] as $relator => $xpath) {
    foreach ($xml->xpath("$xpath/DISS_name") as $contributor) {
      $inverted_name = "{$contributor->DISS_surname}, {$contributor->DISS_fname}";
      if (!empty($contributor->DISS_middle)) {
        $inverted_name .= " $contributor->DISS_middle";
      }
      if (!empty($contributor->DISS_suffix)) {
        $inverted_name .= ", $contributor->DISS_suffix";
      }

      $node_metadata['field_linked_agent'][] = [
        'rel_type' => $relator,
        'target_id' => load_or_create_term([
          'name' => $inverted_name,
          'vid' => 'person',
        ]),
      ];
    }
  }

  // Replace with term ID as a defaults lookup above. Double-check relator.
  $node_metadata['field_linked_agent'][] = [
    'rel_type' => 'relators:pbl',
    'target_id' => load_or_create_term([
      'name' => 'Arizona State University',
      'vid' => 'corporate_body',
    ]),
  ];

  // Notes.
  $degree = (string) current($xml->xpath('DISS_description/DISS_degree'));
  $institution_name = (string) current($xml->xpath('DISS_description/DISS_institution/DISS_inst_name'));
  $institution_contact = (string) current($xml->xpath('DISS_description/DISS_institution/DISS_inst_contact'));

  $requirement_p = Paragraph::create([
    'type' => 'complex_note',
    'field_note_text' => "Partial requirement for: $degree, $institution_name, {$node_metadata['field_edtf_date_created']}",
  ]);
  $requirement_p->save();
  $node_metadata['field_note_para'][] = [
    'target_id' => $requirement_p->id(),
    'target_revision_id' => $requirement_p->getRevisionId(),
  ];

  $field_of_study_p = Paragraph::create([
    'type' => 'complex_note',
    'field_note_text' => "Field of study: $institution_contact",
  ]);
  $field_of_study_p->save();
  $node_metadata['field_note_para'][] = [
    'target_id' => $field_of_study_p->id(),
    'target_revision_id' => $field_of_study_p->getRevisionId(),
  ];

  // Subjects.
  $subjects = [];
  foreach ($xml->xpath('DISS_description/DISS_categorization/DISS_category/DISS_cat_desc') as $cat_desc) {
    $subjects[] = (string) $cat_desc;
  }
  foreach ($xml->xpath('DISS_description/DISS_categorization/DISS_keyword') as $keywords_element) {
    $subjects = array_unique(array_merge($subjects, array_map('trim', explode(',', (string) $keywords_element))));
  }
  // Lookup/create subject terms.
  $node_metadata['field_subject'] = array_map(fn($name): array => [
    'target_id' => load_or_create_term([
      'name' => $name,
      'vid' => 'subject',
    ]),
  ], $subjects);

  // Embargo.
  $accept_date = date_create_from_format('m/d/Y', current($xml->xpath('DISS_description/DISS_dates/DISS_accept_date')));
  $release_date = date_create_from_format('m/d/Y', current($xml->xpath('DISS_restriction/DISS_sales_restriction/@remove')));
  $embargo_code = (int) $xml->attributes()->embargo_code;
  if ($release_date) {
    $node_metadata['field_embargo_release_date'] = $release_date->format($embargo_field_format);
  }
  elseif (1 <= $embargo_code && $embargo_code < 4) {
    /*
     * 0 = no embargo
     * 1 = 6 months
     * 2 = 1 year
     * 3 = 2 years
     * 4 = embargo until date set in proquest xml
     *     DISS_sales_restriction 'remove' attribute
     */
    $additions = ['0 days', '6 months', '1 year', '2 years'];
    $release_date = new DateTime("{$accept_date->format('Y-m-d')} +{$additions[$embargo_code]}");
    $node_metadata['field_embargo_release_date'] = $release_date->format($embargo_field_format);
  }

  // Primary work product.
  if ($diss_filename = (string) current($xml->xpath('DISS_content/DISS_binary'))) {
    $diss_path = $extract_destination . DIRECTORY_SEPARATOR . $diss_filename;
    $diss_name = ($degree_type == 'doctoral') ? 'Dissertation' : 'Thesis';
    $media = create_media($diss_path, $diss_name);
    $node_metadata['field_work_products'][] = ['target_id' => $media->id()];
  }

  // Attachments.
  foreach ($xml->xpath('DISS_content/DISS_attachment') as $attachment) {
    $attachment_path = current(glob($extract_destination . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . (string) $attachment->DISS_file_name));
    $attachment_name = (string) $attachment->DISS_file_descr;

    $media = create_media($attachment_path, $attachment_name);
    $node_metadata['field_work_products'][] = ['target_id' => $media->id()];
  }

  // Create node. (Move after media later.)
  $node = $ns->create($node_metadata);
  $node->save();
  $this->io()->writeln("Created '{$node->label()} ({$node->id()}) from ETD $etd_id");

  // Move Zip file to completed location.
  rename($zip_path, $processed_zip_dir . DIRECTORY_SEPARATOR . $zip_name);
}
