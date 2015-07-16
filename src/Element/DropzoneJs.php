<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\src\Element.
 */

namespace Drupal\dropzonejs\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\File;

/**
 * Provides a DropzoneJS atop of the file element.
 *
 * @FormElement("dropzonejs")
 */
class DropzoneJs extends File {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => array(
        array($class, 'processDropzoneJs'),
      ),
      '#size' => 60,
      '#pre_render' => array(
        array($class, 'preRenderDropzoneJs'),
      ),
      '#theme' => 'dropzonejs',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Processes a dropzone upload element, make use of #multiple if present.
   */
  public static function processDropzoneJs(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attached']['library'][] = 'dropzonejs/dropzonejs';
    $element['uploaded_files'] = [
      '#type' => 'hidden',
      // @todo Handle defaults.
      '#value' => '',
    ];

    return $element;
  }

  /**
   * Prepares a #type 'dropzone' render element for dropzonejs.html.twig.
   *
   * For assistance with handling the uploaded file correctly, see the API
   * provided by file.inc.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #name, #size, #description, #required,
   *   #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderDropzoneJs($element) {
    static::setAttributes($element, ['dropzone-enable']);
    return $element;
  }
}
