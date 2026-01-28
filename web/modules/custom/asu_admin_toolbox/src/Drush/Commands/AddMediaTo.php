<?php

namespace Drupal\asu_admin_toolbox\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command to add media to Drupal nodes.
 */
final class AddMediaTo extends DrushCommands {

  /**
   * Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Drupal file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Constructs a new AddMediaTo object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Create an instance from the service container.
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * Add media to a Drupal node.
   *
   * This command copies a file into the Drupal filesystem, creates a media
   * entity of an appropriate type, and associates it with a node.
   *
   * @param string $nid
   *   The Node ID to attach the media to.
   * @param string $media_use
   *   The Media Use taxonomy term name/label.
   * @param string $path
   *   The path to the file to load.
   * @param array $options
   *   The options array.
   *
   * @command add-media-to
   * @aliases amt
   *
   * @usage drush add-media-to 123 "Original File" /tmp/myfile.pdf
   *   Add a PDF file and attach it to node 123 as an Original File.
   * @usage drush add-media-to --csv=/path/to/batch.csv
   *   Add files from a CSV file with columns: nid, media_use, path.
   */
  #[CLI\Command(name: 'add-media-to', aliases: ['amt'])]
  #[CLI\Argument(name: 'nid', description: 'The Node ID to attach the media to.')]
  #[CLI\Argument(name: 'media_use', description: 'The Media Use taxonomy term name (label).')]
  #[CLI\Argument(name: 'path', description: 'The path to the file to load.')]
  #[CLI\Option(name: 'csv', description: 'Path to a CSV file with columns: nid, media_use, path.')]
  public function addMediaTo(?string $nid = NULL, ?string $media_use = NULL, ?string $path = NULL, array $options = ['csv' => self::REQ]): void {
    // Handle CSV batch mode.
    if (!empty($options['csv'])) {
      $this->addMediaToFromCsv($options['csv']);
      return;
    }

    // Validate that all three arguments are provided for single file mode.
    if (empty($nid) || empty($media_use) || empty($path)) {
      throw new \Exception("Either all three arguments (nid, media_use, path) or --csv option must be provided.");
    }

    $this->processSingleAddition($nid, $media_use, $path);
  }

  /**
   * Process a single media addition operation.
   *
   * @param string $nid
   *   The Node ID.
   * @param string $media_use
   *   The Media Use term name.
   * @param string $path
   *   The file path.
   */
  protected function processSingleAddition(string $nid, string $media_use, string $path): void {
    // Validate that the node exists early.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    if (!$node) {
      throw new \Exception("Node with ID $nid does not exist.");
    }

    // Get the media use taxonomy term early.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $media_use,
      'vid' => 'islandora_media_use',
    ]);

    if (empty($terms)) {
      throw new \Exception("Media Use taxonomy term '$media_use' not found in islandora_media_use vocabulary.");
    }

    $media_use_term = reset($terms);

    // Validate that the file exists.
    if (!file_exists($path)) {
      throw new \Exception("File does not exist: $path");
    }

    if (!is_readable($path)) {
      throw new \Exception("File is not readable: $path");
    }

    // Determine media type and field name based on file type.
    $file_info = $this->getMediaTypeInfo($path);
    if (!$file_info) {
      throw new \Exception("Could not determine media type for file: $path");
    }

    [$media_type, $field_name] = $file_info;

    // Copy the file to the private filesystem in a year-month subdirectory.
    $year_month = date('Y-m');
    $destination_dir = 'private://' . $year_month . '/';

    // Ensure the directory exists.
    $real_path = $this->fileSystem->realpath($destination_dir);
    if (!$real_path) {
      $this->fileSystem->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);
    }

    $file_name = basename($path);
    $destination = $destination_dir . $file_name;

    try {
      $file_content = file_get_contents($path);
      $file_uri = $this->fileSystem->saveData($file_content, $destination, FileSystemInterface::EXISTS_RENAME);
    }
    catch (\Exception $e) {
      throw new \Exception("Failed to copy file into Drupal filesystem: " . $e->getMessage());
    }

    // Create a File entity.
    $file = File::create([
      'uri' => $file_uri,
      'uid' => \Drupal::currentUser()->id(),
    ]);
    $file->save();

    // Create a Media entity.
    $media = Media::create([
      'bundle' => $media_type,
      'uid' => \Drupal::currentUser()->id(),
      'field_media_of' => [
        ['target_id' => $nid],
      ],
      'field_media_use' => [
        ['target_id' => $media_use_term->id()],
      ],
      $field_name => [
        ['target_id' => $file->id()],
      ],
    ]);
    $media->save();

    $this->logger()->notice("Successfully added media '{file}' and created media entity {mid} for node {nid} with media use '{media_use}'.", [
      'file' => $file_name,
      'mid' => $media->id(),
      'nid' => $nid,
      'media_use' => $media_use,
    ]);
  }

  /**
   * Add media from a CSV file.
   *
   * @param string $csv_path
   *   Path to the CSV file.
   */
  protected function addMediaToFromCsv(string $csv_path): void {
    if (!file_exists($csv_path)) {
      throw new \Exception("CSV file does not exist: $csv_path");
    }

    if (!is_readable($csv_path)) {
      throw new \Exception("CSV file is not readable: $csv_path");
    }

    $file_handle = fopen($csv_path, 'r');
    if (!$file_handle) {
      throw new \Exception("Could not open CSV file: $csv_path");
    }

    // Get the directory containing the CSV file for resolving relative paths.
    $csv_dir = dirname(realpath($csv_path));

    $row_count = 0;
    $success_count = 0;
    $error_count = 0;

    while (($row = fgetcsv($file_handle)) !== FALSE) {
      $row_count++;

      // Skip empty rows.
      if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
        continue;
      }

      // Validate that the row has at least 3 columns.
      if (count($row) < 3) {
        $this->logger()->error("Row {row_num}: Invalid row format. Expected 3 columns (nid, media_use, path), got {count}.", [
          'row_num' => $row_count,
          'count' => count($row),
        ]);
        $error_count++;
        continue;
      }

      $nid = trim($row[0]);
      $media_use = trim($row[1]);
      $path = trim($row[2]);

      // Skip empty values.
      if (empty($nid) || empty($media_use) || empty($path)) {
        $this->logger()->warning("Row {row_num}: Skipped due to empty values.", ['row_num' => $row_count]);
        continue;
      }

      // Resolve relative paths relative to the CSV directory.
      if (!str_starts_with($path, '/')) {
        $path = $csv_dir . '/' . $path;
      }

      try {
        $this->processSingleAddition($nid, $media_use, $path);
        $success_count++;
      }
      catch (\Exception $e) {
        $this->logger()->error("Row {row_num} (NID: {nid}, File: {path}): {error}", [
          'row_num' => $row_count,
          'nid' => $nid,
          'path' => $path,
          'error' => $e->getMessage(),
        ]);
        $error_count++;
      }
    }

    fclose($file_handle);

    $this->logger()->notice("CSV processing complete. Total rows: {total}, Successful: {success}, Errors: {errors}.", [
      'total' => $row_count,
      'success' => $success_count,
      'errors' => $error_count,
    ]);
  }

  /**
   * Determine the media type and field name based on file extension/mime type.
   *
   * @param string $path
   *   The file path.
   *
   * @return array|null
   *   An array with [media_type, field_name] or NULL if not determinable.
   */
  protected function getMediaTypeInfo(string $path): ?array {
    $filename = strtolower(basename($path));
    $mime_type = mime_content_type($path);

    // Image files.
    if (str_contains($mime_type, 'image') ||
        str_contains($filename, '.jpg') ||
        str_contains($filename, '.jpeg') ||
        str_contains($filename, '.png') ||
        str_contains($filename, '.gif')) {
      return ['image', 'field_media_image'];
    }

    // TIFF and JP2 files (special case - use 'file' media type).
    if (str_contains($filename, '.tif') ||
        str_contains($filename, '.jp2') ||
        str_contains($filename, '.jpf')) {
      return ['file', 'field_media_file'];
    }

    // Audio files.
    if (str_contains($mime_type, 'audio')) {
      return ['audio', 'field_media_audio_file'];
    }

    // Video files.
    if (str_contains($filename, '.mp4') ||
        str_contains($filename, '.webm') ||
        str_contains($mime_type, 'video')) {
      return ['video', 'field_media_video_file'];
    }

    // PDF and document files.
    if (str_contains($filename, '.pdf') ||
        str_contains($filename, '.doc') ||
        str_contains($filename, '.docx') ||
        str_contains($mime_type, 'pdf') ||
        str_contains($mime_type, 'word') ||
        str_contains($mime_type, 'officedocument')) {
      return ['document', 'field_media_document'];
    }

    // Default fallback for other files.
    return ['file', 'field_media_file'];
  }

}
