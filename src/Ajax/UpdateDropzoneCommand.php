<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\Ajax\UpdateDropzoneCommand.
 */

namespace Drupal\dropzonejs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to update the uploaded_files field associated with
 * a dropzone.
 */
class UpdateDropzoneCommand implements CommandInterface {

  /**
   * The dropzone's CSS selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * The uploaded file name.
   *
   * @var string
   */
  protected $fileName;

  /**
   * UpdateDropzoneCommand constructor.
   *
   * @param string $selector
   *   CSS selector for the dropzone.
   * @param string $filename
   *   The uploaded file name.
   */
  public function __construct($selector, $filename) {
    $this->selector = $selector;
    $this->fileName = $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateDropzone',
      'filename' => $this->fileName,
    ];
  }

}
