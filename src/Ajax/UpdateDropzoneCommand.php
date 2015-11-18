<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\Ajax\UpdateDropzoneCommand.
 */

namespace Drupal\dropzonejs\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Defines an Ajax command which updates a dropzone element with a list of
 * successfully uploaded file names.
 */
class UpdateDropzoneCommand implements CommandInterface {

  /**
   * The form element to be updated.
   *
   * @var array
   */
  protected $element;

  /**
   * @var string
   */
  protected $files = [];

  /**
   * UpdateDropzoneCommand constructor.
   *
   * @param array $element
   *   The form element to be updated.
   */
  public function __construct(array $element) {
    $this->element = $element;
  }

  /**
   * Adds to the list of files to be updated in the dropzone element.
   *
   * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The uploaded file name, or an UploadedFile object.
   *
   * @return $this
   */
  public function addFile($file) {
    $this->files[] = $file instanceof UploadedFile ? $file->getFilename() : (string) $file;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'update_dropzone',
      'selector' => 'input[name="' . $this->element['uploaded_files']['#name'] . '"]',
      'files' => array_unique($this->files),
    ];
  }

}
