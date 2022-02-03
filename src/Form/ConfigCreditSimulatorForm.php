<?php

namespace Drupal\clc_credit_simulator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;

/**
 * ConfigCreditSimulatorForm class.
 *
 * Form credit simulator configuration.
 */
class ConfigCreditSimulatorForm extends FormBase {
  /**
   * Messenger.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * State.
   *
   * @var Drupal\Core\State\State
   */
  protected $state;

  /**
   * Construc a new ConfigCreditSimulatorForm.
   *
   * @param Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param Drupal\Core\State\State $state
   *   The state.
   */
  public function __construct(Messenger $messenger, State $state) {
    $this->messenger = $messenger;
    $this->state = $state;
  }

  /**
   * Create function.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'config_credit_simulator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->state->get('config_credit_simulator', NULL);

    // Create link to share.
    $url = Url::fromRoute('clc_credit_simulator.clc_report');
    $reports_link = Link::fromTextAndUrl('Ver Reporte de simulación de crédito', $url)->toRenderable();
    $form['link_reports'] = [
      '#markup' => render($reports_link),
    ];

    $form['legal'] = [
      '#type' => 'details',
      '#title' => $this->t('Legal'),
      '#open' => FALSE,
      'information' => [
        '#type' => 'textarea',
        '#title' => $this->t('Legal information'),
        '#default_value' => isset($config['legal_information']) ? $config['legal_information'] : NULL,
        '#required' => TRUE,
      ],
    ];

    $form['rates'] = [
      '#type' => 'details',
      '#title' => $this->t('Rates'),
      '#open' => FALSE,
      'iva' => [
        '#type' => 'textfield',
        '#title' => $this->t('IVA rate'),
        '#placeholder' => $this->t('Please enter IVA rate'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['iva']) ? $config['iva'] : 0,
        '#required' => TRUE,
      ],
      'aval' => [
        '#type' => 'textfield',
        '#title' => $this->t('AVAL rate'),
        '#placeholder' => $this->t('Please enter AVAL rate'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['aval']) ? $config['aval'] : 0,
        '#required' => TRUE,
      ],
      'interest' => [
        '#type' => 'textfield',
        '#title' => $this->t('Interest rate'),
        '#placeholder' => $this->t('Please enter interest rate'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['interest']) ? $config['interest'] : 0,
        '#required' => TRUE,
      ],
      'credit_insurance' => [
        '#type' => 'textfield',
        '#title' => $this->t('Credit insurance'),
        '#placeholder' => $this->t('Please enter credit insurance'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['credit_insurance']) ? $config['credit_insurance'] : 0,
        '#required' => TRUE,
      ],
      'amount_insurance' => [
        '#type' => 'textfield',
        '#title' => $this->t('Insurance amount for motorcycle'),
        '#placeholder' => $this->t('Please enter amount insurance for motorcycle'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['amount_insurance']) ? $config['amount_insurance'] : 0,
        '#required' => TRUE,
      ],
      'fee' => [
        '#type' => 'textfield',
        '#title' => $this->t('Fee management'),
        '#placeholder' => $this->t('Please enter fee management'),
        '#attributes' => ['class' => ['field-percent']],
        '#default_value' => isset($config['fee']) ? $config['fee'] : 0,
        '#required' => TRUE,
      ],
      'quotes' => [
        '#type' => 'checkboxes',
        '#title' => $this->t('Quotas available'),
        '#options' => [
          12 => 12,
          24 => 24,
          36 => 36,
          48 => 48,
          60 => 60,
          72 => 72,
          84 => 84,
          96 => 96,
        ],
        '#default_value' => isset($config['quotes']) ? $config['quotes'] : NULL,
        '#required' => TRUE,
      ],
    ];

    $form['requirements'] = [
      '#type' => 'details',
      '#title' => $this->t('Requirements'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    for ($i = 1; $i <= 5; $i++) {
      $reqs = $config['requirements'];
      $key = 'req' . $i;

      $form['requirements'][$key] = [
        '#type' => 'container',
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Requirement ' . $i,
          '#default_value' => isset($reqs[$key]) ? $reqs[$key]['name'] : NULL,
        ],
      ];
    }

    $form['ws'] = [
      '#type' => 'details',
      '#title' => $this->t('Web Services Information'),
      '#open' => FALSE,
      'ws_endpoint' => [
        '#type' => 'textfield',
        '#title' => $this->t('Endpoint'),
        '#placeholder' => $this->t('Please the url endpoint'),
        '#attributes' => ['class' => ['field-endpoint']],
        '#default_value' => isset($config['ws_endpoint']) ? $config['ws_endpoint'] : NULL,
      ],
      'ws_user' => [
        '#type' => 'textfield',
        '#title' => $this->t('User'),
        '#placeholder' => $this->t('Please enter the Web Service user'),
        '#attributes' => ['class' => ['field-ws-user']],
        '#default_value' => isset($config['ws_user']) ? $config['ws_user'] : NULL,
      ],
      'ws_password' => [
        '#type' => 'textfield',
        '#title' => $this->t('Password'),
        '#placeholder' => $this->t('Please enter the Web Service password'),
        '#attributes' => ['class' => ['field-ws-password']],
        '#default_value' => isset($config['ws_password']) ? $config['ws_password'] : NULL,
      ],
      'ws_pqr' => [
        '#type' => 'textfield',
        '#title' => $this->t('PQR Namespace'),
        '#placeholder' => $this->t('Please enter the Web Service namespace PQR'),
        '#attributes' => ['class' => ['field-ws-namespace']],
        '#default_value' => isset($config['ws_pqr']) ? $config['ws_pqr'] : NULL,
      ],
      'ws_debug' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Activate debug'),
        '#attributes' => ['class' => ['field-ws-debug']],
        '#default_value' => isset($config['ws_debug']) ? $config['ws_debug'] : NULL,
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    // Libraries.
    $form['#attached'] = [
      'library' => ['clc_credit_simulator/config'],
    ];

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
    // Form values.
    $values = $form_state->getValues();

    // Save the configuration.
    $config = [
      'terms' => $values['terms'],
      'legal_information' => $values['information'],
      // 'briefing' => $values['briefing'],
      'iva' => $values['iva'],
      'aval' => $values['aval'],
      'interest' => $values['interest'],
      'credit_insurance' => $values['credit_insurance'],
      'amount_insurance' => $values['amount_insurance'],
      'fee' => $values['fee'],
      'requirements' => $values['requirements'],
      'ws_endpoint' => $values['ws_endpoint'],
      'ws_user' => $values['ws_user'],
      'ws_password' => $values['ws_password'],
      'ws_pqr' => $values['ws_pqr'],
      'ws_debug' => $values['ws_debug'],
      'quotes' => array_filter($values['quotes']),
    ];
    $this->state->set('config_credit_simulator', $config);
    $this->messenger()->addMessage('La configuración ha sido guardada.');
  }

}
