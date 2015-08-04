<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\DropzoneJsUploadSaveInterface.
 */

namespace Drupal\dropzonejs;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;

/**
 * Provides an interface for classes that save DropzoneJs uploads.
 */
interface DropzoneJsUploadSaveInterface {

  /**
   * Save a uploaded file.
   *
   * Note: files beeing saved using this method are still flagged as temporary.
   *
   * @param string $uri
   *   The path to the file we want to upload.
   * @param string $destination
   *   A string containing the URI that the file should be copied to. This must
   *   be a stream wrapper URI.
   * @param string $extensions
   *   A space separated list of valid extensions.
   * @param \Drupal\Core\Session\AccountProxyInterfac $user
   *   The owner of the file.
   *
   * @return int|bool
   *   The id of the newly created file entity or false if saving failed.
   *
   * @todo Add possibility to add more validators.
   */
  public function saveFile($uri, $destination, $extensions, AccountProxyInterface $user);

  /**
   * Prepare a file entity from uri.
   *
   * @param string $uri
   *   File's uri.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The owner of the file.
   *
   * @return \Drupal\file\FileInterface
   *   A new entity file entity object, not saved yet.
   */
  public function fileEntityFromUri($uri, AccountProxyInterface $user);

  /**
   * Rename potentially executable files.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity object.
   *
   * @return bool
   *   Whether the file was renamed or not.
   */
  public function renameExecutableExtensions(FileInterface $file);

  /**
   * Validate the uploaded file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity object.
   * @param string $extensions
   *   A space separated list of allowed extensions.
   *
   * @return array
   *   An array containing validation error messages.
   */
  public function validateFile(FileInterface $file, $extensions);

  /**
   * Validate and set destination the destination URI.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity object.
   * @param string $destination
   *   A string containing the URI that the file should be copied to. This must
   *   be a stream wrapper URI.
   *
   * @return bool
   *   True if the destination was sucesfully validated and set, otherwise
   *   false.
   */
  public function prepareDestination(FileInterface $file, $destination);
}
