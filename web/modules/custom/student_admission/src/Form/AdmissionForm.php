<?php

namespace Drupal\student_admission\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Student admission application form.
 */
final class AdmissionForm extends FormBase {

  public function __construct(
    private readonly Connection $database,
    private readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'student_admission_application_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if ($reference = $form_state->get('submitted_reference')) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['admission-success']],
        'icon' => [
          '#markup' => '<span class="admission-success__icon" aria-hidden="true">&#10003;</span>',
        ],
        'title' => [
          '#markup' => '<h2>' . $this->t('Application received') . '</h2>',
        ],
        'message' => [
          '#markup' => '<p>' . $this->t('Thank you. Our admissions team will contact you after reviewing your details.') . '</p>',
        ],
        'reference' => [
          '#markup' => '<p class="admission-success__reference">' . $this->t('Application reference: <strong>@reference</strong>', ['@reference' => $reference]) . '</p>',
        ],
      ];
    }

    $form['#attributes']['class'][] = 'admission-form';

    $form['personal'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['admission-form__section']],
      'heading' => [
        '#markup' => '<div class="admission-form__heading"><span>01</span><div><h2>' . $this->t('Personal details') . '</h2><p>' . $this->t('Tell us how we can reach you.') . '</p></div></div>',
      ],
      'fields' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['admission-form__grid']],
      ],
    ];
    $form['personal']['fields']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' => TRUE,
      '#maxlength' => 100,
      '#autocomplete' => 'given-name',
    ];
    $form['personal']['fields']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#required' => TRUE,
      '#maxlength' => 100,
      '#autocomplete' => 'family-name',
    ];
    $form['personal']['fields']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#maxlength' => 254,
      '#autocomplete' => 'email',
    ];
    $form['personal']['fields']['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#required' => TRUE,
      '#maxlength' => 32,
      '#autocomplete' => 'tel',
      '#description' => $this->t('Include your country or area code.'),
    ];
    $form['personal']['fields']['date_of_birth'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of birth'),
      '#required' => TRUE,
    ];

    $form['academic'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['admission-form__section']],
      'heading' => [
        '#markup' => '<div class="admission-form__heading"><span>02</span><div><h2>' . $this->t('Academic preferences') . '</h2><p>' . $this->t('Choose the path you want to pursue.') . '</p></div></div>',
      ],
      'fields' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['admission-form__grid']],
      ],
    ];
    $form['academic']['fields']['program'] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred program'),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a program -'),
      '#options' => [
        'Computer Science & Engineering' => $this->t('Computer Science & Engineering'),
        'Business Administration' => $this->t('Business Administration'),
        'Medical Sciences' => $this->t('Medical Sciences'),
        'Arts & Humanities' => $this->t('Arts & Humanities'),
        'Other / Undecided' => $this->t('Other / Undecided'),
      ],
    ];
    $form['academic']['fields']['qualification'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latest qualification'),
      '#required' => TRUE,
      '#maxlength' => 160,
      '#placeholder' => $this->t('For example: Intermediate / A Levels'),
    ];
    $form['academic']['fields']['percentage'] = [
      '#type' => 'number',
      '#title' => $this->t('Marks / percentage'),
      '#min' => 0,
      '#max' => 100,
      '#step' => 0.01,
      '#field_suffix' => '%',
    ];
    $form['academic']['fields']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Questions or additional information'),
      '#maxlength' => 2000,
      '#rows' => 4,
      '#attributes' => ['class' => ['admission-form__full']],
    ];

    $form['consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I confirm that these details are accurate and agree to be contacted about my application.'),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit application'),
      '#attributes' => ['class' => ['admission-form__submit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $date = new DrupalDateTime($form_state->getValue('date_of_birth'));
    $today = new DrupalDateTime('today');
    if ($date > $today) {
      $form_state->setErrorByName('date_of_birth', $this->t('Date of birth cannot be in the future.'));
    }

    $phone = preg_replace('/[^0-9]/', '', $form_state->getValue('phone'));
    if (strlen($phone) < 7) {
      $form_state->setErrorByName('phone', $this->t('Enter a valid phone number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $reference = 'ADM-' . date('Y') . '-' . strtoupper(substr(hash('sha256', uniqid('', TRUE)), 0, 6));

    $this->database->insert('student_admission_application')
      ->fields([
        'reference' => $reference,
        'first_name' => trim($form_state->getValue('first_name')),
        'last_name' => trim($form_state->getValue('last_name')),
        'email' => trim($form_state->getValue('email')),
        'phone' => trim($form_state->getValue('phone')),
        'date_of_birth' => $form_state->getValue('date_of_birth'),
        'program' => $form_state->getValue('program'),
        'qualification' => trim($form_state->getValue('qualification')),
        'percentage' => $form_state->getValue('percentage') !== '' ? $form_state->getValue('percentage') : NULL,
        'message' => trim($form_state->getValue('message') ?? ''),
        'status' => 'new',
        'created' => $this->time->getRequestTime(),
      ])
      ->execute();

    $form_state->set('submitted_reference', $reference);
    $form_state->setRebuild(TRUE);
  }

}
