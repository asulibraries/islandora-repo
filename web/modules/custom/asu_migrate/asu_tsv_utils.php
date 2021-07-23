<?php

/**
 * @file asu_tsv_utils.php
 */

// Set any defaults or initialize the values to pass to parsing command.
$input_file = $output_file = '';
$n = 5;
$offset = 0;

if (!empty($argv[1])) {
  $p1 = $argv[1];
  if ($p1 == 'help' || $p1 == '--help' || $p1 == '/?') {
    help($argv);
    return;
  }
  $input_file = $p1;
  if (!empty($argv[2])) {
    $p2 = $argv[2];
    if (strstr($p2, '-n=') || strstr($p2, '--number=')) {
      $n = str_replace(['--number=', '-n='], '', $p2) + 0;
      if (!is_int($n)) {
        print 'ERROR: number parameter is not an integer value. Got "' . $n . '".' . "\n\n";
        help($argv);
        return;
      }
    }
    elseif (strstr($p2, '-o=') || strstr($p2, '--offset=')) {
      $offset = str_replace(['--offset=', '-o='], '', $p2) + 0;
      if (!is_int($n)) {
        print 'ERROR: offset parameter is not an integer value. Got "' . $n . '".' . "\n\n";
        help($argv);
        return;
      }
    }
    else {
      $output_file = $p2;
    }
  }
  if (!empty($argv[3])) {
    $p3 = $argv[3];
    if (strstr($p3, '-n=') || strstr($p3, '--number=')) {
      $n = str_replace(['--number=', '-n='], '', $p3) + 0;
      if (!is_int($n)) {
        print 'ERROR: number parameter is not an integer value. Got "' . $n . '".' . "\n\n";
        help($argv);
        return;
      }
    }
    elseif (strstr($p3, '-o=') || strstr($p3, '--offset=')) {
      $offset = str_replace(['--offset=', '-o='], '', $p3) + 0;
      if (!is_int($n)) {
        print 'ERROR: offset parameter is not an integer value. Got "' . $n . '".' . "\n\n";
        help($argv);
        return;
      }
    }
    else {
      $output_file = $p3;
    }
  }
}
if (!$input_file) {
  print 'ERROR: input-file missing.' . "\n\n";
  help($argv);
  return;
}
parse_tsv($input_file, $n, $offset, $output_file);

/**
 * Parses the TSV file.
 *
 * @param string $input_file
 *   Points to the TSV file that is to be parsed.
 * @param int $n
 *   How many rows to parse.
 * @param int $offset
 *   Offset for line parsing.
 * @param string $output_file
 *   File to which to save the result CSV.
 */
function parse_tsv($input_file, int $n, int $offset = 0, string $output_file = '') {
  echo "\nWorking on parsing up to $n rows of the file \"$input_file\".\n\n";
  if (!file_exists($input_file)) {
    print "ERROR: File \"$input_file\" does not exist. Could not parse.\n\n";
    help();
    exit;
  }
  $handle = fopen($input_file, "r");
  $remaining_lines = $n;
  $csv = [];
  if ($handle) {
    while ((($line = fgets($handle)) !== FALSE) && ($remaining_lines || $offset)) {
      if ($offset) {
        $offset--;
        echo "skipping this due to offset [" . $offset . "]\n";
      }
      else {
        // Process the line read.
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
        $identifier = str_replace('http://hdl.handle.net/2286/', '', $hdl_string);
        if ($output_file) {
          // This step may depend on whether or not this script will ALSO be
          // loading the CSV merged metadata file that came from the export
          // from the legacy repository for the same items.
          $csv[$identifier] = convert_marc_arr_to_csv_row($line_as_array);
        }
        $remaining_lines--;
      }
    }
    fclose($handle);
    if ($output_file) {
      $out_csv = convert_row_to_unified_fields($csv);
      save_csv_to_file($out_csv, $output_file);
    }
  }
  else {
  }
  die('done');
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
  foreach ($line_parts as $i => $value) {
    // Echo "in [" . (1 + $i) . "] " . $value . "\n";.
    $field_values = refactor_marc_field($value);
    echo "out[" . (1 + $i) . "] " . print_r($field_values, TRUE) . "\n";
    foreach ($field_values as $k => $v) {
      $return_marc_array[$k][] = $v;
    }
    echo "-------------\n";
  }
  echo print_r($return_marc_array, TRUE) . "---  F I N A L  ------\n\n";
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
    // Echo " {} " . implode(", ", $curly_bracket_parts) . "\n";.
    foreach ($curly_bracket_parts as $curly_bracket_part) {
      // Echo "\nsub refactor field processing \n";.
      $individual_parts = explode(",", $curly_bracket_part);
      if (count($individual_parts) == 1) {
        // Demote the field-value pair because this is not a pre-coordinated
        // subject string.
        echo "will need to DEMOTE FOR " . print_r($curly_bracket_parts, TRUE) . "\n";
      }
      foreach ($individual_parts as $i => $individual_part) {
        echo "processing part (" . ($i + 1) . " of " . count($individual_parts) . "): $individual_part\n";
        $fvs = refactor_marc_field($individual_part);
        foreach ($fvs as $f => $v) {
          $ret_arr[$f][] = $v;
        }
      }
    }
    if (count($individual_parts) == 1) {
      return $ret_arr;
    }
    else {
      return [$field_name => $ret_arr];
    }
  }
  else {
    $bracket_part = get_part_between_chars($value, "[", "]");
    $value = strip_end_quotes($value);
    if ($bracket_part) {
      echo " [] " . implode(", ", $bracket_part) . "\n";
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
  if (strstr($value, $open_char) && strstr($value, $close_char) &&
    (strpos($value, $open_char) < strpos($value, $close_char))) {
    // Different characters need to be escaped.
    if ($open_char == "[") {
      $pattern = "#\\" . $open_char . "(.*?)\\" . $close_char . "#";
    }
    else {
      $pattern = "#" . $open_char . "(.*?)" . $close_char . "#";
    }
    preg_match_all($pattern, $value, $matches);
    $value = substr($value, 1, (strpos($value, $open_char) - 1));
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
  $ret_array = [];
  foreach ($line_as_array as $field_name => $array) {
    $field_name = get_csv_field_headername($field_name);
    if (count($array) == 1) {
      $val0_of_array = $array[0];
      if (is_array($val0_of_array)) {
      }
      else {
        $ret_array[$field_name] = $val0_of_array;
      }
    }
    else {
    }
  }
  return $ret_array;
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
  // Perhaps this needs to be mapped from the migration header names.
  return $field_name;
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
      else {
        fputcsv($fp, $row);
      }
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
  foreach ($out_csv as $identifier => $row) {
    $fieldnames = array_keys($row);
    $key_fieldnames = array_merge($fieldnames, $key_fieldnames);
  }
  // Now $key_fieldnames should have all of the distinct fieldnames.
  foreach ($out_csv as $identifier => $row) {
    $fieldnames = array_keys($row);
    foreach ($key_fieldnames as $fieldname) {
      $unified_csv[$identifier][$fieldname] = $row[$fieldname];
    }
  }
  return $unified_csv;
}

/**
 * Help / info for this utility.
 */
function help($argv = []) {
  print "The \"ASU TSV utility\" will parse a tab-separated file and optionally save this as a file.

Usage: asu_tsv_utils.php [input-file] [output-file] ([OPTIONS])

Mandatory argument
  [input-file]           relative path to the TSV file to parse.

Optional argument
  [output-file]          where to save the results

(OPTIONS)
  -n=#, --number=#       how many rows to process (default is 5)
  -o=#, --offset=#       how many rows by which to offset (default is 0)

Parse the TSV contained in the input-file and optionally save as a CSV file that has headings row that is made up of the distinct field references.\n\n";

  die(print_r($argv));
}
