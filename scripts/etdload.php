<?php

/**
 * @file
 * Etdload.
 *
 * Loads ETDs delivered by ProQuest.
 * Run using Drush:
 * `drush scr scripts/etdload.php -- <dir for proquest zips> <collection nid>`
 */

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

$ns = \Drupal::entityTypeManager()->getStorage('node');
$ms = \Drupal::entityTypeManager()->getStorage('media');
$ts = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
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
// @todo replace string values with term IDs.
$degree_map = [
  "masters" => "Masters Thesis",
  "doctoral" => "Doctoral Dissertation",
];
$language_map = [
// English.
  "en" => "eng",
// Spanish.
  "es" => "spa",
// Chinese.
  "zh" => "chi",
// French.
  "fr" => "fre",
// German.
  "de" => "ger",
// Italian.
  "it" => "ita",
// Japanese.
  "ja" => "jpn",
// Korean.
  "ko" => "kor",
// Portuguese.
  "pt" => "por",
// Russian.
  "ru" => "rus",
];

// Load defaults.
// Collection we are populating w/ node ID passed from commandline.
// @todo get ID from command-line.
// 149053
$collection_nid = $extra[1];

// DateTimeFormat for embargo release date.
$embargo_field_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;

// Terms (to be replaced with actual term lookups when testing with Drupal.)
$copyright_term = "http://rightsstatements.org/vocab/InC/1.0/";
$reuse_term = 'All Rights Reserved';
$theses_term = "http://id.loc.gov/authorities/genreForms/gf2014026039";

foreach (array_filter(scandir($path), function ($value) {
  return str_ends_with($value, '.zip');
}) as $zip_name) {
  $etd_id = basename($zip_name, '.zip');

  $zip = new ZipArchive();
  $zip_path = $path . DIRECTORY_SEPARATOR . $zip_name;
  if ($zip->open($zip_path) !== TRUE) {
    // $this->io()->error("Could not open {$zip_path}!");
    print("Could not open {$zip_path}!\n");
    continue;
  }

  $extract_destination = $export_dir . DIRECTORY_SEPARATOR . $etd_id;

  $zip->extractTo($extract_destination);
  $zip->close();

  $xml = simplexml_load_file(current(glob($extract_destination . DIRECTORY_SEPARATOR . '*_DATA.xml')));
  if (!$xml) {
    print("Could not find _DATA.xml in $extract_destination!\n");
    continue;
  }
  // Load all the elements for each field.
  $node_metadata = ['field_member_of' => ['target_id' => $collection_nid]];
  // @todo populate field_title as a paragraph.
  $title = (string) current($xml->xpath('DISS_description/DISS_title'));
  $node_metadata['title'] = $title;
  $node_metadata['field_title'] = ['field_main_title' => $title];
  $node_metadata['field_edtf_date_created'] = (string) current($xml->xpath('DISS_description/DISS_dates/DISS_comp_date'));
  $node_metadata['field_extent'] = (string) current($xml->xpath('DISS_description/@page_count')) . ' pages';
  $node_metadata['field_rich_description'] = '';
  foreach ($xml->xpath('DISS_content/DISS_abstract/DISS_para') as $para) {
    $node_metadata['field_rich_description'] .= "<p>$para</p>";
  }

  // Term reference fields.
  // @todo term lookups.
  $node_metadata['field_language'] = $language_map[(string) current($xml->xpath('DISS_description/DISS_categorization/DISS_language'))];
  $node_metadata['field_copyright_statement'] = $copyright_term;
  $node_metadata['field_reuse_permissions'] = $reuse_term;
  $node_metadata['field_genre'][] = $degree_map[(string) current($xml->xpath('DISS_description/@type'))];
  $node_metadata['field_genre'][] = $theses_term;

  // Contributors.
  // @todo double-check relators.
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

      // @todo replace name with new term lookup/create.
      $node_metadata['field_linked_agent'][] = ['rel_type' => $relator, 'target' => $inverted_name];
    }
  }

  // Replace with term ID as a defaults lookup above. Double-check relator.
  $node_metadata['field_linked_agent'][] = ['rel_type' => 'relators:pbl', 'target' => 'Arizona State University'];

  // Notes.
  $degree = (string) current($xml->xpath('DISS_description/DISS_degree'));
  $institution_name = (string) current($xml->xpath('DISS_description/DISS_institution/DISS_inst_name'));
  $institution_contact = (string) current($xml->xpath('DISS_description/DISS_institution/DISS_inst_contact'));
  // @todo create paragraphs.
  $node_metadata['field_note_para'][] = ['field_note_text' => "Partial requirement for: $degree, $institution_name, {$node_metadata['field_edtf_date_created']}"];
  $node_metadata['field_note_para'][] = ['field_note_text' => "Field of study: $institution_contact"];

  // Subjects.
  $subjects = [];
  foreach ($xml->xpath('DISS_description/DISS_categorization/DISS_category/DISS_cat_desc') as $cat_desc) {
    $subjects[] = (string) $cat_desc;
  }
  foreach ($xml->xpath('DISS_description/DISS_categorization/DISS_keyword') as $keywords_element) {
    $subjects = array_unique(array_merge($subjects, array_map('trim', explode(',', (string) $keywords_element))));
  }
  // @todo lookup/create subject terms.
  $node_metadata['field_subject'] = $subjects;

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

  // @todo Primary work product.
  if ($diss_filename = (string) current($xml->xpath('DISS_content/DISS_binary'))) {
    $diss_path = $extract_destination . DIRECTORY_SEPARATOR . $diss_filename;
    print("Dissertation File: {$diss_path}\n");
    // @todo copy file and create media.
    $node_metadata['field_work_products'][] = ['target_id' => $diss_path];
  }

  // @todo Attachments.
  foreach ($xml->xpath('DISS_content/DISS_attachment') as $attachment) {
    $attachment_path = current(glob($extract_destination . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . (string) $attachment->DISS_file_name));
    $attachment_name = (string) $attachment->DISS_file_descr;

    // @todo determine which media to use.
    $ext = pathinfo($attachment_path, PATHINFO_EXTENSION);
    // Default to a File.
    $model = 'file';
    $media_source_field = 'field_media_file';
    // @todo grab supported extensions for each media instead of hard-coded lists.
    // Document.
    if (in_array($ext, [
      'txt', 'rtf', 'doc', 'docx', 'ppt', 'pptx', 'xls',
      'xlsx', 'pdf', 'odf', 'odg', 'odp', 'ods', 'odt',
      'fodt', 'fods', 'fodp', 'fodg', 'key', 'numbers', 'pages',
    ])) {
      $model = 'document';
      $media_source_field = 'field_media_document';
    }
    // Image.
    elseif (in_array($ext, ['png', 'gif', 'jpg', 'jpeg', 'svg'])) {
      $model = 'image';
      $media_source_field = 'field_media_image';
    }
    // Audio.
    elseif (in_array($ext, ['mp3', 'wav', 'aac', 'aif', 'aiff', 'mid', 'flac', 'm4a', 'mp4'])) {
      $model = 'audio';
      $media_source_field = 'field_media_audio_file';
    }
    // Video.
    elseif (in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'dpx'])) {
      $model = 'video';
      $media_source_field = 'field_media_video_file';
    }

    print("Attachment path: $attachment_path\nAttachment Title: $attachment_name\nAttachment Model: $model\n");
    $node_metadata['field_work_products'][] = ['target_id' => $attachment_path];
  }

  print(print_r($node_metadata, TRUE) . "\n");

  // @todo move Zip file to completed location.
  rename($zip_path, $processed_zip_dir . DIRECTORY_SEPARATOR . $zip_name);
}
