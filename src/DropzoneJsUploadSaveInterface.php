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
}
