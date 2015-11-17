<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\Controller\UploadController.
 */

namespace Drupal\dropzonejs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\dropzonejs\Ajax\UpdateDropzoneCommand;
use Drupal\dropzonejs\UploadException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles requests that dropzone issues when uploading files.
 *
 * The uploaded file will be stored in the configured tmp folder and will be
 * added a tmp extension. Further filename processing will be done in
 * Drupal\dropzonejs\Element::valueCallback. This means that the final
 * filename will be provided only after that callback.
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
   * Transliteration service.
   *
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Constructs dropzone upload controller route controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   * @param \Drupal\Core\Transliteration\PhpTransliteration $transliteration
   *   Transliteration service.
   */
  public function __construct(Request $request, ConfigFactoryInterface $config, PhpTransliteration $transliteration) {
    $this->request = $request;
    $tmp_override = $config->get('dropzonejs.settings')->get('tmp_dir');
    $this->temporaryUploadLocation = ($tmp_override) ? $tmp_override : $config->get('system.file')->get('path.temporary');
    $this->trasliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory'),
      $container->get('transliteration')
    );
  }

  /**
   * Handles DropzoneJs uploads.
   *
   * @param \Symfony\Component\HttpFoundation\Request $req
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handleUploads(Request $req) {
    // @todo: Implement file_validate_size();
    try {
      $this->prepareTemporaryUploadDestination();
      $this->handleUpload();
    }
    catch (UploadException $e) {
      return $e->getErrorResponse();
    }

    // Return either an AJAX command or a JSON-RPC response (default).
    $request = $req->request;
    if ($request->has('_drupal_ajax')) {
      $command = new UpdateDropzoneCommand($request->get('selector'), $this->filename);
      return (new AjaxResponse())->addCommand($command);
    }
    else {
      // Return JSON-RPC response.
      return new JsonResponse([
        'jsonrpc' => '2.0',
        'result' => $this->filename,
        'id' => 'id',
      ], 200);
    }
  }

  /**
   * Prepares temporary destination folder for uploaded files.
   *
   * @return bool
   *   TRUE if destination folder looks OK and FALSE otherwise.
   *
   * @throws \Drupal\dropzonejs\UploadException
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
   * @throws \Drupal\dropzonejs\UploadException
   */
  protected function getFilename(UploadedFile $file) {
    if (empty($this->filename)) {
      $original_name = $file->getClientOriginalName();

      // There should be a filename and it should not contain a semicolon,
      // which we use to separate filenames.
      if (!isset($original_name)) {
        throw new UploadException(UploadException::FILENAME_ERROR);
      }

      // Transliterate.
      $processed_filename = \Drupal::transliteration()->transliterate($original_name);

      // For security reasons append the txt extension. It will be removed in
      // Drupal\dropzonejs\Element::valueCallback when we will know the valid
      // extension and we will be abble to properly sanitaze the filename.
      $processed_filename = $processed_filename . '.txt';

      $this->filename = $processed_filename;
    }

    return $this->filename;
  }

  /**
   * Handles multipart uploads.
   *
   * @throws \Drupal\dropzonejs\UploadException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function handleUpload() {
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $this->request->files->get('file');
    if (!$file instanceof UploadedFile) {
      throw new AccessDeniedHttpException();
    }
    elseif ($error = $file->getError() && $error != UPLOAD_ERR_OK) {
      // Check for file upload errors and return FALSE for this file if a lower
      // level system error occurred. For a complete list of errors:
      // See http://php.net/manual/features.file-upload.errors.php.
      switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $message = t('The file could not be saved because it exceeds the maximum allowed size for uploads.');
          continue;

        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          $message = t('The file could not be saved because the upload did not complete.');
          continue;

        // Unknown error.
        default:
          $message = t('The file could not be saved. An unknown error has occurred.');
          continue;
      }

      throw new UploadException(UploadException::FILE_UPLOAD_ERROR, $message);
    }

    // Open temp file.
    $tmp = "{$this->temporaryUploadLocation}/{$this->getFilename($file)}";
    if (!($out = fopen("{$this->temporaryUploadLocation}/{$this->getFilename($file)}", $this->request->request->get('chunk', 0) ? 'ab' : 'wb'))) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    // Read binary input stream.
    $input_uri = $file->getFileInfo()->getRealPath();
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
