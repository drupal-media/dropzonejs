<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\Controller\UploadController.
 */

namespace Drupal\dropzonejs\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\dropzonejs\UploadException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles requests that dropzone issues when uploading files.
 */
class UploadController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  protected $request;

  /**
   * Stores temporary folder URI.
   *
   * This is configurable via the configuration variable. It was added for HA
   * environments where temporary location may need to be a shared across all
   * servers.
   *
   * @var string
   */
  protected $temporaryUploadLocation;

  /**
   * Filename of a file that is being uploaded.
   *
   * @var string
   */
  protected $filename;

  /**
   * Constructs dropzone upload controller route controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   */
  public function __construct(Request $request, ConfigFactoryInterface $config) {
    $this->request = $request;
    $tmp_override = $config->get('dropzonejs.settings')->get('tmp_dir');
    $this->temporaryUploadLocation = ($tmp_override) ? $tmp_override : $config->get('system.file')->get('path.temporary');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory')
    );
  }

  /**
   * Handles DropzoneJs uploads.
   */
  public function handleUploads() {
    // @todo: Implement file_validate_size();
    try {
      $this->prepareTemporaryUploadDestination();
      $this->handleUpload();
    }
    catch (UploadException $e) {
      return $e->getErrorResponse();
    }
  }

  /**
   * Prepares temporary destination folder for uploaded files.
   *
   * @return bool
   *   TRUE if destination folder looks OK and FALSE otherwise.
   *
   * @throws \Drupal\plupload\UploadException
   */
  protected function prepareTemporaryUploadDestination() {
    $writable = file_prepare_directory($this->temporaryUploadLocation, FILE_CREATE_DIRECTORY);
    if (!$writable) {
      throw new UploadException(UploadException::DESTINATION_FOLDER_ERROR);
    }

    // Try to make sure this is private via htaccess.
    file_save_htaccess($this->temporaryUploadLocation, TRUE);
  }

  /**
   * Reads, checks and return filename of a file being uploaded.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   An instance of UploadedFile.
   *
   * @throws \Drupal\plupload\UploadException
   */
  protected function getFilename(UploadedFile $file) {
    if (empty($this->filename)) {
      $this->filename = $file->getClientOriginalName();

      // Check the file name for security reasons; it must contain letters,
      // numbers and underscores.
      if (!preg_match('/[\w\.]/', $this->filename)) {
        throw new UploadException(UploadException::FILENAME_ERROR);
      }
    }

    return $this->filename;
  }

  /**
   * Handles multipart uploads.
   *
   * @throws \Drupal\dropzonejs\UploadException
   * @throws Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function handleUpload() {
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $this->request->files->get('file');
    if (!$file instanceof UploadedFile) {
      throw new AccessDeniedHttpException();
    }
    elseif ($file->getError() != UPLOAD_ERR_OK) {
      throw new UploadException(UploadException::FILE_UPLOAD_ERROR);
    }

    // Open temp file.
    $tmp = $this->temporaryUploadLocation . $this->getFilename($file);
    if (!($out = fopen("{$this->temporaryUploadLocation}/{$this->getFilename($file)}", $this->request->request->get('chunk', 0) ? 'ab' : 'wb'))) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    // Read binary input stream.
    $input_uri = "{$this->temporaryUploadLocation}/{$file->getFilename()}";
    if (!($in = fopen($input_uri, 'rb'))) {
      throw new UploadException(UploadException::INPUT_ERROR);
    }

    // Append input stream to temp file.
    while ($buff = fread($in, 4096)) {
      fwrite($out, $buff);
    }

    // Be nice and keep everything nice and clean.
    // @todo when implementing multipart dont forget to drupal_unlink.
    fclose($in);
    fclose($out);
  }
}
