<?php

namespace Drupal\student_admission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays submitted admission applications.
 */
final class AdmissionAdminController extends ControllerBase {

  public function __construct(
    private readonly Connection $database,
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Builds the admissions listing.
   */
  public function listing(): array {
    $header = [
      $this->t('Reference'),
      $this->t('Student'),
      $this->t('Contact'),
      $this->t('Program'),
      $this->t('Qualification'),
      $this->t('Submitted'),
      $this->t('Status'),
    ];

    $query = $this->database->select('student_admission_application', 'a')
      ->fields('a')
      ->orderBy('created', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $rows = [];

    foreach ($pager->execute() as $application) {
      $rows[] = [
        $application->reference,
        $application->first_name . ' ' . $application->last_name,
        [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<a href="mailto:{{ email }}">{{ email }}</a><br>{{ phone }}',
            '#context' => [
              'email' => $application->email,
              'phone' => $application->phone,
            ],
          ],
        ],
        $application->program,
        $application->qualification,
        $this->dateFormatter->format($application->created, 'short'),
        ucfirst($application->status),
      ];
    }

    return [
      'intro' => [
        '#markup' => '<p>' . $this->t('Applications submitted through the student admission page.') . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No admission applications have been submitted yet.'),
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

}

