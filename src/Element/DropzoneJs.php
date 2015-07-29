<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\src\Element.
 */

namespace Drupal\dropzonejs\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a DropzoneJS atop of the file element.
 *
 * Configuration options are:
 * - #title
 *   The main field title.
 * - #description
 *   Description under the field.
 * - #dropzone_description
 *   Will be visible inside the upload area.
 * - #max_filesize
 *   Used by dropzonejs and expressed in MB. See
 *   http://www.dropzonejs.com/#config-maxFilesize
 *
 * When submitted the element returns an array of temporary file locations. It's
 * the duty of the environment that implements this element to handle the
 * uploaded files.
 *
 * @FormElement("dropzonejs")
 */
class DropzoneJs extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => [[$class, 'processDropzoneJs']],
      '#size' => 60,
      '#pre_render' => [[$class, 'preRenderDropzoneJs']],
      '#theme' => 'dropzonejs',
      '#theme_wrappers' => ['form_element'],
      '#attached' => [
        'library' => ['dropzonejs/dropzonejs', 'dropzonejs/integration']
      ],
    ];
  }

  /**
   * Processes a dropzone upload element, make use of #multiple if present.
   */
  public static function processDropzoneJs(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['uploaded_files'] = [
      '#type' => 'hidden',
      // @todo Handle defaults.
      '#default_value' => '',
    ];

    return $element;
  }

  /**
   * Prepares a #type 'dropzone' render element for dropzonejs.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #description, #required, #attributes,
   *   #dropzone_description, #max_filesize.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderDropzoneJs($element) {
    $element['#attached']['drupalSettings']['dropzonejs'] = [
      'upload_path' => base_path() . 'dropzonejs/upload',
      'instances' => [
        $element['#id'] => [
          'maxFilesize' => $element['#max_filesize'],
          'dictDefaultMessage' => $element['#dropzone_description']
        ],
      ],
    ];

    static::setAttributes($element, ['dropzone-enable']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $file_names = [];
    $return['uploaded_files'] = NULL;

    if ($input !== FALSE) {
      $user_input = NestedArray::getValue($form_state->getUserInput(), $element['#parents'], $key_exists);

      if (!empty($user_input['uploaded_files'])) {
        $file_names = array_filter(explode(';', $user_input['uploaded_files']));
        $temp_path = \Drupal::config('system.file')->get('path.temporary');

        foreach ($file_names as $name) {
          $return['uploaded_files'][] = "$temp_path/$name";
        }
      }
      $form_state->setValueForElement($element, $return);

      return $return;
    }
  }
}
