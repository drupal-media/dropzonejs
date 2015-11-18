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
   * The name of the uploaded file.
   *
   * @var string
   */
  protected $file;

  /**
   * UpdateDropzoneCommand constructor.
   *
   * @param array $element
   *   The form element to be updated.
   * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The uploaded file name, or an UploadedFile object.
   */
  public function __construct(array $element, $file) {
    $this->element = $element;
    $this->file = $file instanceof UploadedFile ? $file->getFilename() : $file;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'update_dropzone',
      'selector' => 'input[name="' . $this->element['uploaded_files']['#name'] . '"]',
      'file' => $this->file,
    ];
  }

}
