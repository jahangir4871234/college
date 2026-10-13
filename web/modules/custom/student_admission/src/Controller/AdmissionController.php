<?php

namespace Drupal\student_admission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\student_admission\Form\AdmissionForm;

/**
 * Builds the public student admissions page.
 */
final class AdmissionController extends ControllerBase {

  /**
   * Returns the admissions landing page.
   */
  public function page(): array {
    return [
      '#theme' => 'student_admission_page',
      '#form' => $this->formBuilder()->getForm(AdmissionForm::class),
      '#attached' => [
        'library' => ['student_admission/admission_page'],
      ],
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}

