<?php

namespace Drupal\clc_credit_simulator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CreditSimulatorForm. The form for the Credit simulator.
 */
class CreditSimulatorForm extends FormBase {
  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * State.
   *
   * @var Drupal\Core\State\State
   */
  protected $state;

  /**
   * Current Route Match.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;

  /**
   * The configuration.
   *
   * @var config
   */
  private $config;

  /**
   * The price.
   *
   * @var config
   */
  private $price;

  /**
   * CreditSimulatorForm constructor.
   *
   * @param Drupal\Core\Database\Connection $connection
   *   The connection.
   * @param Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param Drupal\Core\State\State $state
   *   The state.
   * @param Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The routeMatch.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   */
  public function __construct(Connection $connection, Messenger $messenger, LoggerChannelFactoryInterface $logger, State $state, CurrentRouteMatch $currentRouteMatch, RequestStack $request) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->state = $state;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->request = $request->getCurrentRequest();

    // Get data current product.
    $node = $this->currentRouteMatch->getParameter('node');
    $node_type = $node->type->getValue();
    $node_type = reset($node_type);
    if ($node_type['target_id'] == 'bike') {
      // Get configuration module.
      $this->config = $this->state->get('config_credit_simulator', NULL);

      $title = $node->title->getValue();
      $this->title = reset($title);
      $price = $node->field_price->getValue();
      $this->price = reset($price);
      $reference = $node->field_reference->getValue();
      $this->reference = reset($reference);
      $interes_rate = $node->field_interest_rate->getValue();
      $legal_text = $node->field_legal_text->getValue();

      // Interest rate of motorcycle.
      if (!empty($interes_rate)) {
        $interes_rate = reset($interes_rate);
        $this->config['interest'] = $interes_rate['value'];
      }

      // Legal text of motorcycle.
      if (!empty($legal_text)) {
        $legal_text = reset($legal_text);
        $this->config['legal_information'] = $legal_text['value'];
      }
    }
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('state'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'clc_credit_simulator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!empty($this->config)) {
      // Product information.
      $price = $this->price['value'];

      // Create the url terms and conditions.
      $domain = $this->request->getSchemeAndHttpHost();
      $label_terms = $this->t('Terms and Conditions');
      $url = Url::fromUri('https://www.tdpcorbeta.com/', [
        'query' => ['page' => $domain],
        'attributes' => ['target' => '_blank'],
      ]);
      $link = Link::fromTextAndUrl($label_terms, $url)->toRenderable();

      // Payments method available.
      $options = $this->config['quotes'];

      // Labels translate.
      $label_step_one = $this->t('Step one');
      $label_price = $this->t('Price');
      $label_fee = $this->t('Enter value of initial fee');
      $label_payments = $this->t('Select the payments numbers');
      $label_btn_step_first = $this->t('Calculate');

      // Container step first.
      $form['step_first'] = [
        '#type' => 'fieldset',
        '#title' => $label_step_one,
        '#attributes' => ['class' => ['active']],
        'price' => [
          '#prefix' => '<h4 class="field-price">',
          '#suffix' => '</h4>',
          '#markup' => $label_price . ': <span>' . $price . '</span>',
        ],
        'fee' => [
          '#type' => 'textfield',
          '#title' => $label_fee,
          '#attributes' => [
            'class' => ['field-numeric'],
            'placeholder' => 0,
          ],
        ],
        'payments' => [
          '#type' => 'select',
          '#title' => $label_payments,
          '#options' => $options,
        ],
        'btn_step_first' => [
          '#prefix' => '<span class="next-step" id="edit-btn-step-first">',
          '#suffix' => '</span>',
          '#markup' => $label_btn_step_first,
        ],
      ];

      // Labels step second.
      $label_step_second = $this->t('Step second');
      $label_btn_step_second = $this->t('Next');

      // Container step second.
      $form['step_second'] = [
        '#type' => 'fieldset',
        '#title' => $label_step_second,
        // Get main structure step second.
        'summary' => $this->summaryStructure(),
        'btn_step_second' => [
          '#prefix' => '<span class="next-step">',
          '#suffix' => '</span>',
          '#markup' => $label_btn_step_second,
        ],
      ];

      // Labels Step third.
      $label_step_third = $this->t('Step third');
      $label_field_name = $this->t('Name');
      $label_field_lastname = $this->t('Lastname');
      $label_field_cedula = $this->t('Cédula');
      $label_field_email = $this->t('Email');
      $label_field_phone = $this->t('Phone');
      $label_field_cellphone = $this->t('Cellphone');
      $label_field_state = $this->t('State');
      $label_field_city = $this->t('City');
      $label_btn_submit = $this->t('Send');

      // Container step third.
      $form['step_third'] = [
        '#type' => 'fieldset',
        '#title' => $label_step_third,
        'name' => [
          '#type' => 'textfield',
          '#title' => $label_field_name . '*',
          '#required' => TRUE,
        ],
        'lastname' => [
          '#type' => 'textfield',
          '#title' => $label_field_lastname . '*',
          '#required' => TRUE,
        ],
        'cedula' => [
          '#type' => 'number',
          '#title' => $label_field_cedula . '*',
          '#required' => TRUE,
        ],
        'email' => [
          '#type' => 'email',
          '#title' => $label_field_email . '*',
          '#required' => TRUE,
        ],
        'phone' => [
          '#type' => 'number',
          '#title' => $label_field_phone,
        ],
        'cellphone' => [
          '#type' => 'number',
          '#title' => $label_field_cellphone . '*',
          '#required' => TRUE,
        ],
        'state' => [
          '#type' => 'select',
          '#title' => $label_field_state . '*',
          '#options' => [],
          '#required' => TRUE,
        ],
        'container_city' => [
          '#type' => 'container',
          'city' => [
            '#type' => 'select',
            '#title' => $label_field_city . '*',
            '#options' => [],
            '#required' => TRUE,
          ],
        ],
        'terms' => [
          '#type' => 'checkbox',
          '#title' => '*Autorizo el uso de mis datos en los siguientes ',
          '#description' => render($link),
          '#required' => TRUE,
        ],
        'submit' => [
          '#type' => 'submit',
          '#value' => $label_btn_submit,
          '#name' => 'btn-submit',
        ],
      ];

      // Finance amount.
      $form['finance_amount'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['finance-amount']],
      ];
      // Payment fee.
      $form['payment_fee'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['payment-fee']],
      ];

      // Libraries.
      $form['#attached'] = [
        'library' => ['clc_credit_simulator/simulator'],
        'drupalSettings' => [
          'simulator_config' => [
            'amount' => $price,
            'iva' => $this->config['iva'],
            'aval' => $this->config['aval'],
            'credit_insurance' => $this->config['credit_insurance'],
            'amount_insurance' => $this->config['amount_insurance'],
            'fee' => $this->config['fee'],
            'interest' => $this->config['interest'],
          ],
        ],
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
    $triggering = $form_state->getTriggeringElement();

    // Only submit form response or if form state values populated.
    if (!empty($triggering) && $triggering['#name'] == 'btn-submit') {
      // Values entered.
      $values = $form_state->getValues();

      // Data product.
      $product_name = $this->title['value'];
      $reference = $this->reference['value'];
      // Structure to contact message.
      $initial_fee = $values['fee'] ? $values['fee'] : '0';

      $comment_field = "AKT Electric\n";
      $comment_field .= "Producto a financiar: {$product_name}";
      $comment_field .= "\n--------------";
      $comment_field .= "\na {$values['payments']} meses";
      $comment_field .= "\nPago inicial: {$initial_fee}";
      $comment_field .= "\nMonto a financiar: {$values['finance_amount']}";
      $comment_field .= "\nPago mensual: {$values['payment_fee']}";
      $comment_field .= "\nEl precio de la moto incluye IVA. No incluye el costo de los trámites de tránsito (Matrícula, SOAT y placa). Las imágenes que aquí se publican son informativas. Las especificaciones pueden variar sin previo aviso.";

      $fields = [
        'tipo_documento'    => 'C',
        'documento_cliente' => $values['cedula'],
        'nombre_cliente' => $values['name'],
        'apellido_cliente' => $values['lastname'],
        'email' => $values['email'],
        'telefono' => $values['phone'],
        'celular' => $values['cellphone'],
        'ciudad' => $values['city'],
        'tema' => 4,
        'comentario' => $comment_field,
        'autorizo' => 'SI',
        'unidad' => 'Akt',
        'referencia' => $reference,
      ];
      $this->createCasoContacto($fields);

      // Save submittion.
      $fields_submittions = [
        'product_name' => $product_name,
        'total_fee' => $values['payments'] . ' meses',
        'amount' => $values['finance_amount'],
        'customer_document' => $values['cedula'],
        'customer_name' => $values['name'],
        'customer_lastname' => $values['lastname'],
        'customer_email' => $values['email'],
        'customer_phone' => $values['phone'],
        'customer_city' => $values['city'],
        'comment' => $comment_field,
        'terms_and_conditions' => 1,
        'created' => REQUEST_TIME,
      ];

      $this->connection->insert('clc_credit_simulator_submittions')
        ->fields($fields_submittions)
        ->execute();
      $this->messenger->addMessage($this->t('Your quote has been sent successfully'));
    }
  }

  /**
   * Ajax Callback.
   */
  public function ajaxCallbackForm(array &$form, FormStateInterface $form_state) {

    return $form['step_third']['container_city']['city'];
  }

  /**
   * {@inheritdoc}
   */
  private function summaryStructure() {
    $li = [];
    foreach ($this->config['requirements'] as $value) {
      if ($value['name']) {
        $li[] = [
          '#prefix' => '<li class="item-list">',
          '#suffix' => '</li>',
          '#markup' => $value['name'],
        ];
      }
    }

    $interest = $this->config['interest'] * 100;
    $legal_information = str_replace('{interest}', $interest, $this->config['legal_information']);

    return [
      'fga' => [
        '#prefix' => '<div class="summary-item item-fga">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Valor del fondo de garantías - FGA*'),
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '$0.000',
        ],
      ],
      'fee_numbers' => [
        '#prefix' => '<div class="summary-item item-fee-numbers">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Fee numbers'),
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '24',
        ],
      ],
      'finance_amount' => [
        '#prefix' => '<div class="summary-item item-finance-amount">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Finance amount') . ' (' . $this->t('Total amount') . ')',
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '$2.000.000',
        ],
      ],
      'life_insurance' => [
        '#prefix' => '<div class="summary-item item-life-insurance">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Life insurance'),
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '$0.000',
        ],
      ],
      'moto_insurance' => [
        '#prefix' => '<div class="summary-item item-moto-insurance">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Motorcycle insurance'),
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '$0.000',
        ],
      ],
      'payment_fee' => [
        '#prefix' => '<div class="summary-item item-payment-fee">',
        '#suffix' => '</div>',
        'label' => [
          '#prefix' => '<span class="item-label">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Monthly Fee'),
        ],
        'value' => [
          '#prefix' => '<span class="item-value">',
          '#suffix' => '</span>',
          '#markup' => '$0.000',
        ],
      ],
      'requirements' => !empty($li) ? [
        '#prefix' => '<div class="item-requirements">',
        '#suffix' => '</div>',
        'link' => [
          '#prefix' => '<span class="elem-trigger" data-container="#container-requirements">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Requirements that you need'),
        ],
        'content' => [
          '#prefix' => '<div id="container-requirements" class="item-modal">',
          '#suffix' => '</div>',
          'ol' => [
            '#prefix' => '<ol>',
            '#suffix' => '</ol>',
            'li' => $li,
          ],
        ],
      ] : NULL,
      'legal' => [
        '#prefix' => '<div class="item-legal">',
        '#suffix' => '</div>',
        'link' => [
          '#prefix' => '<span class="elem-trigger" data-container="#container-legal-information">',
          '#suffix' => '</span>',
          '#markup' => $this->t('Legal information'),
        ],
        'content' => [
          '#prefix' => '<div id="container-legal-information" class="item-modal">',
          '#suffix' => '<span id="sufi" class="sufi"></span></div>',
          '#markup' => $legal_information,
        ],
      ],
    ];
  }

  /**
   * Web service UpdateBuyerByMotorcycle data.
   */
  private function createCasoContacto($params) {
    $config = $this->state->get('config_credit_simulator', NULL);

    if (isset($config['ws_endpoint'])) {
      $endpoint = $config['ws_endpoint'];
      $params['usuario'] = $config['ws_user'];
      $params['password'] = $config['ws_password'];

      if ($config['ws_debug']) {
        $this->logger('WS CRM Create caso')->notice('Parametros enviados: ' . json_encode($params));
      }

      try {
        // Web service Call.
        $client = new \nusoap_client($endpoint, TRUE);
        $client->namespaces['pqr'] = $config['ws_pqr'];
        $result = $client->call('crea_caso', $params);

        if ($config['ws_debug']) {
          $this->logger('WS CRM Create caso')->notice('Respuesta: ' . json_encode($result));
        }
      }
      catch (Exception $e) {
        // @Watchdog
        $this->logger('credit_simulator')->error('Error al consumir servicio Simulador de crédito');
      }

      return $result;
    }
  }

}
