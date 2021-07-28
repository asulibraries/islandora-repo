<?php

/**
 * @file
 * merge_repo_and_tsv.php
 */

require_once 'handle_id_map.php';

// Set any defaults or initialize the values to pass to parsing command.
$tsv_file = $csv_file = $output_file = '';
$n = 5;
$offset = 0;

if (!empty($argv[1])) {
  $parts = $argv;
  foreach ($parts as $i => $param) {
    if ($param == '-help' || $param == '--help' || $param == '/?') {
      help();
    }
    // Skip the first - which would be this script filename.
    if ($i > 0) {
      @list($param_n, $param_v) = explode("=", $param);
      switch ($param_n) {
        case '-tsv':
          $tsv_file = $param_v;
          break;

        case '-csv':
          $csv_file = $param_v;
          break;

        case '-out':
          $output_file = $param_v;
          break;

        case '-n':
        case '--number':
          $n = $param_v + 0;
          break;

        case '-o':
        case '--offset':
          $offset = $param_v + 0;
          break;
      }
    }
    if (!is_int($n)) {
      echo "ERROR: number parameter is not an integer value. Got \"" . $n . "\".\n\n";
      help();
    }
    if (!is_int($offset)) {
      echo "ERROR: offset parameter is not an integer value. Got \"" . $offset . "\".\n\n";
      help();
    }
  }
}
if (!$tsv_file) {
  echo "ERROR: input TSV file missing.\n\n";
  help();
}
if (!$csv_file) {
  echo "ERROR: input CSV file missing.\n\n";
  help();
}
$tsv_as_csv = parse_tsv($tsv_file, $n, $offset, $output_file);
$find_item_ids = array_keys($tsv_as_csv);
$csv_headers = [];
$repo_csv = load_repo_csv_file($csv_file, $find_item_ids, $csv_headers);
// Since the load function removes the item_ids that have been found, the
// array now contains the item_id values that were not found in the CSV.
if (count($find_item_ids) > 0) {
  echo "\nItem ids that were not found in Repo CSV source file (" .
    number_format(count($find_item_ids)) . " identifiers):" .
    "\n-------------------------\n" . implode(", ", $find_item_ids) . "\n\n";
}
if (count($tsv_as_csv) > 0) {
  if ($output_file) {
    // Now, for the items that were not found in the CSV, write a new TSV file.
    if (count($find_item_ids) > 0) {
      save_tsv_of_unfound_items($tsv_file, $n, $offset, $find_item_ids);
    }
    remove_unfound_items_from_tsv($tsv_as_csv, $find_item_ids);
    // At this point, we can start to merge the TSV and the CSV.
    $distinctfields_csv = merge_multifields($tsv_as_csv);
    $out_csv = convert_row_to_unified_fields($distinctfields_csv);
    merge_tsv_and_csv($out_csv, $repo_csv);
    save_csv_to_file($out_csv, $output_file);
  }
  else {
    echo "WARNING: Resultant CSV file was not saved.\n";
  }
}
else {
  echo "WARNING: The parsed TSV did not result in any rows.\n";
}
die("\nDone.\n\n");

/**
 * Parses the TSV file.
 *
 * @param string $tsv_file
 *   Points to the TSV file that is to be parsed.
 * @param int $n
 *   How many rows to parse.
 * @param int $offset
 *   Offset for line parsing.
 * @param string $output_file
 *   File to which to save the result CSV.
 */
function parse_tsv($tsv_file, int $n, int $offset = 0, string $output_file = '') {
  echo "(each \"*\" represents 100 items or rows)\n\nWorking on parsing up to $n rows of the file \"$tsv_file\".\n";
  if (!file_exists($tsv_file)) {
    echo "ERROR: File \"$tsv_file\" does not exist. Could not parse.\n\n";
    help();
  }
  $handle = fopen($tsv_file, "r");
  $remaining_lines = $n;
  $csv = [];
  if ($handle) {
    while ((($line = fgets($handle)) !== FALSE) && ($remaining_lines || $offset)) {
      if ($offset) {
        $offset--;
      }
      else {
        show_progress($remaining_lines, $n);
        // Process the line read.
        // First, be sure that there is no linefeed as part of this value.
        $line = rtrim($line, "\n");
        $line_parts = explode("	", $line);
        $line_as_array = refactor_marc_array($line_parts);
        // These rows will uniquely be identified by their identifier (parsed
        // from the ($hdl_string) value
        // and would match up with items from the legacy repository on the same
        // identifier.
        if (array_key_exists('handle', $line_as_array)) {
          $hdl_element = $line_as_array['handle'];
          $hdl_string = array_shift($hdl_element);
        }
        else {
          $hdl_string = '????';
        }
        $identifier = get_identifier($hdl_string);
        if ($output_file) {
          // This step may depend on whether or not this script will ALSO be
          // loading the CSV merged metadata file that came from the export
          // from the legacy repository for the same items.
          $csv[$identifier] = array_merge(['Item ID' => $identifier], convert_marc_arr_to_csv_row($line_as_array));
        }
        $remaining_lines--;
      }
    }
    fclose($handle);
    echo "\nParsed " . number_format($n - $remaining_lines) . " lines of TSV file.\n";
  }
  return $csv;
}

/**
 * Helper to return the identifier value from our handle.net address values.
 *
 * @param string $hdl_string
 *   The handle URI.
 *
 * @return string
 *   The identifier that is parsed from it.
 */
function get_identifier($hdl_string) {
  global $_asu_migrate_hdl_map;
  $search = ['http://hdl.handle.net/2286/R.I.', 'http://hdl.handle.net/2286/'];
  $identifier = str_ireplace($search, '', $hdl_string);
  return (array_key_exists($identifier, $_asu_migrate_hdl_map) ? $_asu_migrate_hdl_map[$identifier] : $identifier);
}

/**
 * Helper to refactor the value for each TSV element of a line.
 *
 * @param array $line_parts
 *   Variable parameter. Incoming array representing a line from the TSV file.
 *
 * @return mixed
 *   This should be the identifier field taken from the handle field.
 */
function refactor_marc_array(array &$line_parts) {
  $return_marc_array = [];
  foreach ($line_parts as $value) {
    $field_values = refactor_marc_field($value);
    foreach ($field_values as $k => $v) {
      $return_marc_array[$k][] = $v;
    }
  }
  return $return_marc_array;
}

/**
 * Helper to normalize the field name from TSV.
 *
 * Can support multiple values inside of curly brackets and adds an element
 * to array parts that have a vocab_or_qualifier that is wrapped in [] brackets.
 *
 * @param string $value
 *   Incoming field name value - should contain ":".
 *
 * @return array
 *   The value put into a [field_name => value ] array.
 */
function refactor_marc_field($value) {
  @list($field_name, $value) = explode(":", $value, 2);
  // If this field value has multiple fields, recursively pass them through
  // this same function to process.
  $ret_arr = [];
  $curly_bracket_parts = get_part_between_chars($value, "{", "}");
  if ($curly_bracket_parts) {
    foreach ($curly_bracket_parts as $curly_bracket_part) {
      $quoted_parts = get_part_between_chars($curly_bracket_part, '"', '"');
      foreach ($quoted_parts as $quoted_part) {
        $comma_tokenized = str_replace(',', '&comma;', $quoted_part);
        $curly_bracket_part = str_replace($quoted_part, $comma_tokenized, $curly_bracket_part);
      }
      $individual_parts = explode(",", $curly_bracket_part);
      // Echo "Individual parts from curly brackets contents :\n" .
      // print_r($individual_parts, TRUE) . "\n-------\n";.
      foreach ($individual_parts as $individual_part) {
        $individual_part = str_replace('&comma;', ',', $individual_part);
        $fvs = refactor_marc_field($individual_part);
        foreach ($fvs as $f => $v) {
          if (is_array($v) && count($v) == 1 && array_key_exists(0, $v)) {
            $ret_arr[$f][] = $v;
          }
          else {
            $ret_arr[$f][] = $v;
          }
        }
      }
    }
    // Do we need to demote when only one item in pre-coordinated subjects?
    if (count($individual_parts) == 1) {
      return [$field_name => $ret_arr];
    }
    else {
      return [$field_name => $ret_arr];
    }
  }
  else {
    $bracket_part = get_part_between_chars($value, "[", "]");
    $value = strip_end_quotes($value);
    if ($bracket_part) {
      return [
        $field_name => [
          0 => [
            'vocab_or_qualifier' => $bracket_part,
            0 => $value,
          ],
        ],
      ];
    }
    else {
      return [$field_name => $value];
    }
  }
}

/**
 * Helper to strip quote characters from beginning and end of string.
 *
 * @param string $v
 *   The incoming value to normalize.
 *
 * @return string
 *   The string value with the quotes stripped from beginning and end.
 */
function strip_end_quotes($v) {
  return ltrim(rtrim($v, '"'), '"');
}

/**
 * Helper to return values that are between the open_char and close_char.
 *
 * NOTE: this only handles "[", "]" OR "{", "}".
 *
 * @param string $value
 *   The string to operate upon.
 * @param string $open_char
 *   The starting character. "[" or "{".
 * @param string $close_char
 *   The end character. "]" or "}".
 *
 * @return array
 *   Array of matches of what's found in $value.
 */
function get_part_between_chars(&$value, $open_char, $close_char) {
  if (($open_char == '"' && strstr($value, $open_char)) ||
    (strstr($value, $open_char) && strstr($value, $close_char) &&
    (strpos($value, $open_char) < strpos($value, $close_char)))) {
    // Different characters need to be escaped.
    if ($open_char == "[") {
      $pattern = "#\\" . $open_char . "(.*?)\\" . $close_char . "#";
    }
    elseif ($open_char == '"') {
      $pattern = '#\"(.*?)\"#';
    }
    else {
      $pattern = "#" . $open_char . "(.*?)" . $close_char . "#";
    }
    preg_match_all($pattern, $value, $matches);
    if ($open_char <> '"') {
      $value = substr($value, 1, (strpos($value, $open_char) - 1));
    }
    return $matches[1];
  }
}

/* -------------------------------------------------------------------------- */

/**
 * Helper to convert the marc array into an array.
 *
 * The resultant array can be dumped as CSV for merging this with our migration
 * CSV from legacy repository.
 *
 * @param array $line_as_array
 *   The line to convert.
 */
function convert_marc_arr_to_csv_row(array $line_as_array) {
  foreach ($line_as_array as $field_name => $array) {
    $field_name = get_csv_field_headername($field_name);
    $ret_array[$field_name] = glue_multiparts($array, $field_name);
  }
  return $ret_array;
}

/**
 * Will glue together the parts of a multidimensional array.
 *
 * @param array $array
 *   The incoming array to glue together.
 * @param string $field_name
 *   Field name.
 *
 * @return string
 *   The parts of the multidimensional array glued together.
 */
function glue_multiparts(array $array, $field_name) {
  $output = [];
  foreach ($array as $array_item) {
    $val0_of_array = ((is_array($array_item) && array_key_exists(0, $array_item)) ? $array_item[0] : $array_item);
    // @todo NEED A WAY TO GET ANY vocab_or_qualifier FOR THESE.
    $vocabs_or_qualifiers = '';
    if (is_array($val0_of_array)) {
      if (array_key_exists('vocab_or_qualifier', $val0_of_array)) {
        $vocabs_or_qualifiers = "@" . implode(",@", $val0_of_array['vocab_or_qualifier']);
        unset($val0_of_array['vocab_or_qualifier']);
      }
      if (count($val0_of_array) == 1) {
        $output[] = trimr_period_add_att(glue_multiparts($val0_of_array, $field_name), $vocabs_or_qualifiers);
      }
      else {
        $concat_fields = [];
        foreach ($val0_of_array as $inner_array) {
          $inner_vocabs_or_qualifiers = '';
          if (array_key_exists('vocab_or_qualifier', $inner_array)) {
            $inner_vocabs_or_qualifiers = "@" . implode(",@", $inner_array['vocab_or_qualifier']);
            unset($inner_array['vocab_or_qualifier']);
          }
          $concat_fields[] = trimr_period_add_att(glue_multiparts($inner_array, $field_name), $inner_vocabs_or_qualifiers);
        }
        $sep = ($field_name == 'field_prec_subject') ? '|' : '|';
        $output[] = trimr_period_add_att(implode($sep, $concat_fields), $vocabs_or_qualifiers);
      }
    }
    else {
      $output[] = trimr_period_add_att($val0_of_array, $vocabs_or_qualifiers);
    }
  }
  return implode(" || ", $output);
}

/**
 * Trims the right period from a string and adds the attribute.
 *
 * @param string $string
 *   The incoming string to operate on.
 * @param string $attrib
 *   The attribute from TSV.
 *
 * @return string
 *   The trimmed value with the attrib added.
 */
function trimr_period_add_att($string, $attrib) {
  return rtrim(trim($string), ".") . $attrib;
}

/**
 * This will return the Migration CSV header name corresponding to a fieldname.
 *
 * @param string $field_name
 *   The incoming field_name that came from the TSV export file.
 *
 * @return string
 *   This should be the header name that matches.
 */
function get_csv_field_headername($field_name) {
  /*
   * These field names are possible, but according to the merge rules between
   * the MARC derived TSV and the exported legacy records in the CSV source,
   * some of these fields will never be needed.
   */
  // handle
  // field_title
  // field_statement_responsibility
  // field_date_created
  // field_prec_subject
  // field_subject
  // field_linked_agent
  // field_language
  // field_genre
  // field_note_para
  // field_resource_type
  // field_cataloging_standards
  // field_description_source
  // field_level_of_coding
  // field_extent
  // field_name_subject
  // field_title_subject
  // field_geographic_subject
  // field_table_of_contents.
  $tsv_field_map = [
    '' => 'Collection ID',
    '' => 'Collection Title',
    '' => 'Item Title',
    '' => 'Item ID',
    '' => 'Subjects',
    '' => 'Personal Contributors',
    '' => 'Institutional Contributors',
    '' => 'Date Created',
    '' => 'Description',
    '' => 'Table of Contents',
    '' => 'Language',
    '' => 'Identifiers',
    '' => 'Series',
    '' => 'Resource Types',
    '' => 'Extent',
    '' => 'Citation',
    '' => 'Notes',
    '' => 'Copyright',
    '' => 'Reuse',
    '' => 'Visibility',
    '' => 'Embargo Date',
    '' => 'System Created',
    '' => 'System Updated',
    '' => 'Attachment Count',
    '' => 'System User',
    '' => 'Model',
    '' => 'Parent Item',
    '' => 'Complex Object Child',
    '' => 'Topical Subject',
    '' => 'Topical Subjects',
    '' => 'Contributors-Person',
    '' => 'Contributors-Corporate',
  ];
  return (array_key_exists($field_name, $tsv_field_map) ? $tsv_field_map[$field_name] : $field_name);
}

/**
 * This will iterate through each row of the TSV and build the merged CSV.
 *
 * @param array $tsv
 *   The array of TSV items' data.
 * @param array $repo_csv
 *   The array of Repo CSV items' data.
 *
 * @return array
 *   The unified array - ready to save.
 */
function merge_tsv_and_csv(array $tsv, array $repo_csv) {
  $tsv_and_csv = [];
  foreach ($tsv as $id => $row) {
    if ($id && is_array($row)) {
      // Do nothing.
    }
  }
  foreach ($repo_csv as $id => $row) {
    if ($id && is_array($row)) {
      // Do nothing.
    }
  }
  return $tsv_and_csv;
}

/**
 * To save the CSV to file.
 *
 * @param array $out_csv
 *   The unified CSV file for saving.
 * @param string $output_file
 *   The filename for saving.
 */
function save_csv_to_file(array $out_csv, $output_file) {
  // Open the $output_file for writing and write each part using fputcsv().
  if (count($out_csv) < 1) {
    echo "WARNING: There was nothing to save";
    return;
  }

  if ($output_file) {
    $fp = @fopen($output_file, 'w');
    if (!$fp) {
      echo "ERROR: The file \"" . $output_file . "\" could not be opened for writing.";
      return;
    }
    $first_row = TRUE;
    foreach ($out_csv as $row) {
      if ($first_row) {
        fputcsv($fp, array_keys($row));
        $first_row = FALSE;
      }
      fputcsv($fp, $row);
    }
    fclose($fp);
  }
}

/**
 * Helper to make each row have same keys.
 *
 * This code makes each row have the same exact field names and in the same
 * order so that a single header row would match for the subsequent rows too.
 *
 * @param array $out_csv
 *   Array of string value that contains all rows.
 *
 * @return array
 *   The unified array - ready to save.
 */
function convert_row_to_unified_fields(array $out_csv) {
  $key_fieldnames = $unified_csv = [];
  $counter = 0;
  echo "\nFinding distinct set of fieldnames from TSV file.\n";
  foreach ($out_csv as $identifier => $row) {
    $fieldnames = array_keys($row);
    $key_fieldnames = array_merge($fieldnames, $key_fieldnames);
    show_progress($counter);
    $counter++;
  }
  // Now $key_fieldnames should have all of the distinct fieldnames.
  $counter = 0;
  echo "\nStoring unified TSV fields as CSV structure for items.\n";
  foreach ($out_csv as $identifier => $row) {
    foreach ($key_fieldnames as $fieldname) {
      $unified_csv[$identifier][$fieldname] = (array_key_exists($fieldname, $row) ? $row[$fieldname] : '');
    }
    show_progress($counter);
    $counter++;
  }
  return $unified_csv;
}

/**
 * Removes the items that were not found from being processed any further.
 *
 * @param array $tsv_as_csv
 *   Variable parameter of TSV file - with item id values as the key.
 * @param array $find_item_ids
 *   The array of item ids that were identified as not found in the CSV.
 */
function remove_unfound_items_from_tsv(array &$tsv_as_csv, array $find_item_ids) {
  foreach ($find_item_ids as $item_id) {
    if (array_key_exist($item_id, $tsv_as_csv)) {
      unset($tsv_as_csv[$item_id]);
    }
  }
}

/**
 * This will scan the incoming array and combine the multiple values.
 *
 * @param array $csv
 *   Incoming array to scan.
 *
 * @return array
 *   The same as incoming array, but these array items are "flattened".
 */
function merge_multifields(array $csv) {
  $distinctfields_csv = [];
  $counter = 0;
  echo "\nMerging multiple values for fields in result from TSV file.\n";
  foreach ($csv as $fieldname => $field_contents) {
    show_progress($counter);
    $counter++;
    if (is_array($field_contents)) {
      $vocabs_or_qualifiers = '';
      if (array_key_exists('vocab_or_qualifier', $field_contents)) {
        $vocabs_or_qualifiers = "@" . implode(",@", $field_contents['vocab_or_qualifier']);
        unset($field_contents['vocab_or_qualifier']);
      }
      if ($vocabs_or_qualifiers) {
        $field_contents[] = $vocabs_or_qualifiers;
      }
      $distinctfields_csv[$fieldname] = implode("|", $field_contents);
    }
  }
  $distinctfields_csv = $csv;
  return $distinctfields_csv;
}

/**
 * This will load the source Repo CSV file. Each row will use "Item ID" as key.
 *
 * @param string $filename
 *   Path to source Repo CSV export file.
 * @param array $item_ids
 *   Variable parameter - the Item id values that come from the TSV file.
 * @param array $csv_headers
 *   Variable parameter - returns headers that correspond to the CSV data rows.
 *
 * @return array
 *   An associative array that has "Item ID" as the key.
 */
function load_repo_csv_file($filename, array &$item_ids, array &$csv_headers) {
  $array = [];
  if (($handle = fopen($filename, "r")) !== FALSE) {
    echo "\nWorking on finding matches of TSV items in source Repo CSV file \"$filename\".\n";

    $csv_headers = [];
    $history_json_index = $item_id_index = $counter = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      show_progress($counter);
      if (count($csv_headers) < 1) {
        $csv_headers = $data;
        $history_json_index = array_search('History JSON', $data);
        $item_id_index = array_search('Item ID', $data);
      }
      else {
        if (array_key_exists($item_id_index, $data)) {
          $found_item_id_index = array_search($data[$item_id_index], $item_ids);
          if (!($found_item_id_index === FALSE)) {
            unset($data[$history_json_index]);
            $this_identifier = $item_ids[$found_item_id_index];
            // Also, remove this from the $item_ids array.
            unset($item_ids[$found_item_id_index]);
            $array[$this_identifier] = $data;
          }
        }
        $counter++;
      }
    }
    fclose($handle);
    echo "\n" . number_format(count($array)) . " items matched by scanning through " . number_format($counter) . " Repo items.\n";
  }
  if ($history_json_index && array_key_exists($history_json_index, $csv_headers)) {
    unset($csv_headers[$history_json_index]);
  }
  return $array;
}

/**
 * Saves an UNFOUND_ITEMS_{n}_{offset}_{$tsv_file} file.
 *
 * @param string $tsv_file
 *   Points to the TSV file that was the source.
 * @param int $n
 *   How many rows to parse.
 * @param int $offset
 *   Offset for line parsing.
 * @param array $find_item_ids
 *   The item id values that need to be saved in the UNFOUND_ITEMS file.
 */
function save_tsv_of_unfound_items($tsv_file, $n, $offset, array &$find_item_ids) {
  // Save filename will be "UNFOUND_ITEMS_{n}_{offset}_{$tsv_file}".
  $pathinfo = pathinfo($tsv_file);
  $save_unfound_item_filename = (($pathinfo['dirname']) ? $pathinfo['dirname'] . '/' : '') .
    "UNFOUND_ITEMS_" . $n . "_" . $offset . "_" . $pathinfo['basename'];
  echo "\n\nMaking file of unfound TSV items \"$save_unfound_item_filename\".\n";
  $handle = fopen($tsv_file, "r");
  $remaining_lines = $n;
  $unfound_lines = [];
  if ($handle) {
    while ((($line = fgets($handle)) !== FALSE) && ($remaining_lines || $offset)) {
      if ($offset) {
        $offset--;
      }
      else {
        show_progress($remaining_lines, $n);
        // Process the line read.
        // First, be sure that there is no linefeed as part of this value.
        $line = rtrim($line, "\n");
        $line_parts = explode("	", $line);
        $line_as_array = refactor_marc_array($line_parts);
        // These rows will uniquely be identified by their identifier (parsed
        // from the ($hdl_string) value
        // and would match up with items from the legacy repository on the same
        // identifier.
        if (array_key_exists('handle', $line_as_array)) {
          $hdl_element = $line_as_array['handle'];
          $hdl_string = array_shift($hdl_element);
        }
        else {
          $hdl_string = '????';
        }
        $identifier = get_identifier($hdl_string);
        // Finally, we know whether or not this item is on the list.
        if (!(array_search($identifier, $find_item_ids) === FALSE)) {
          $unfound_lines[] = $line . "\n";
        }

        $remaining_lines--;
      }
    }
    fclose($handle);
    // Save the TSV file for any items now.
    if (count($unfound_lines) > 0) {
      file_put_contents($save_unfound_item_filename, implode("", $unfound_lines));
      echo "\nSaved \"$save_unfound_item_filename\".\n";
    }
    else {
      echo "WARNING: There was at least one item that was not found in the " .
        "Repo CSV source file, but during the attempt to write the " .
        "UNFOUND_ITEMS file, no matching items could be found in TSV file " .
        "either. Nothing was saved.\n";
    }
  }
}

/**
 * Helper to show progress of various iterations.
 *
 * @param int $counter
 *   The current iteration.
 * @param int $max
 *   The maximum iteration.
 */
function show_progress($counter, $max = 0) {
  if ($counter <> $max&& $counter % 25 === 0) {
    if ($counter && $counter % 100 === 0) {
      echo "*";
    }
    else {
      echo ".";
    }
  }
}

/**
 * Help / info for this utility.
 */
function help() {
  echo "The \"Merge Repo and TSV\" will parse a tab-separated file and optionally save this as a file. This will create a CSV for migration purposes; the fields that are kept from each source has been determined by the metadata owners.

Usage: merge_repo_and_tsv.php -tsv={filename} -csv={filename} -out={filename} ([OPTIONS])

Mandatory argument
  -tsv={filename}     relative path to the TSV file to parse.
  -csv={filename}     relative path to the Repo CSV file for the same items.

Optional argument
  -out={filename}     where to save the results

(OPTIONS)
  -n=#                how many rows to process (default is 5)
  -o=#                how many rows by which to offset (default is 0)

Examples:
 php merge_repo_and_tsv.php -tsv=tsv/foo.tsv -csv=bar.csv -out=/tmp/merge_test.csv -n=2000 -o=6000
 php merge_repo_and_tsv.php -tsv=/tmp/test_foo.tsv -csv=/tmp/test_bar.csv -out=~/merge_test.csv -n=10000
 php merge_repo_and_tsv.php -tsv=/tmp/test_foo.tsv -csv=/tmp/test_bar.csv

\n\n";
  exit;
}
