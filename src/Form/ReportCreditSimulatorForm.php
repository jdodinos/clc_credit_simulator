<?php

namespace Drupal\clc_credit_simulator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ReportCreditSimulatorForm. The form for the Report credit simulator.
 */
class ReportCreditSimulatorForm extends FormBase {
  /**
   * The database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;

  /**
   * Construct a new ReportCreditSimulatorForm.
   *
   * @param Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   */
  public function __construct(Connection $connection, RequestStack $request) {
    $this->connection = $connection;
    $this->request = $request;
  }

  /**
   * Create function.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container Interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'report_credit_simulator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Filter created.
    $form['created'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Created date'),
      '#description' => $this->t('Filter by created date'),
      '#tree' => TRUE,
      'from_date' => [
        '#type' => 'date',
        '#title' => $this->t('Date from'),
      ],
      'to_date' => [
        '#type' => 'date',
        '#title' => $this->t('Date to'),
      ],
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#name' => 'filter_report',
    ];

    $filter_date = $this->request->getCurrentRequest()->get('created');
    if (isset($filter_date['to_date']) || isset($filter_date['from_date'])) {
      $data = $this->getInformation();

      $header = [
        'created' => $this->t('Created'),
        'product' => $this->t('Product'),
        'name' => $this->t('Customer name'),
        'lastname' => $this->t('Customer lastname'),
        'document' => $this->t('Customer document'),
        'email' => $this->t('Email'),
        'phone' => $this->t('Phone'),
        'terms' => $this->t('Terms and conditions'),
        'comment' => $this->t('Comment'),
      ];
      $rows = [];

      foreach ($data as $value) {
        $created = date('Y, M j', $value->created);

        $rows[] = [
          'created' => $created,
          'product' => $value->product_name,
          'name' => $value->customer_name,
          'lastname' => $value->customer_lastname,
          'document' => $value->customer_document,
          'email' => $value->customer_email,
          'phone' => $value->customer_phone,
          'terms' => $value->terms_and_conditions ? 'Aceptado' : 'Rechazado',
          'comment' => $value->comment,
        ];
      }

      $form['table'] = [
        '#type' => 'table',
        '#title' => $this->t('Customers'),
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('Without information'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  private function getInformation() {
    $filter_date = $this->request->getCurrentRequest()->get('created');

    // Filter information.
    $query = $this->connection->select('clc_credit_simulator_submittions', 'subm')
      ->fields('subm');

    // Filter by created date to.
    if (!empty($filter_date['to_date'])) {
      $to_created = $filter_date['to_date'];
      $to_created = strtotime($to_created . ' 00:00:01');
      $query->condition('created', $to_created, '<');
    }

    // Filter by created date from.
    if (!empty($filter_date['from_date'])) {
      $from_created = $filter_date['from_date'];
      $from_created = strtotime($from_created . ' 23:59:59');
      $query->condition('created', $from_created, '>');
    }

    $result = $query
      ->orderBy('created', 'DESC')
      ->execute()->fetchAll();

    return $result;
  }

}
