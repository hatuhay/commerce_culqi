<?php

namespace Drupal\commerce_culqi\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
/**
 * Provides the Culqi payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "culqi",
 *   label = "Culqi",
 *   display_label = "Culqi",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_culqi\PluginForm\Culqi\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_culqi/form",
 * )
 */
class Culqi extends OffsitePaymentGatewayBase  {

  /**
   * {@inheritdoc}
   */
  // public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
  //   parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

  //   // \Culqi\Culqi::setApiKey($this->configuration['secret_key']);
  // }

  /**
   * {@inheritdoc}
   */
  public function getPublishableKey() {
    return $this->configuration['publishable_key'];
  }

  public function getPrivateKey() {
    return $this->configuration['secret_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'publishable_key' => 'pk_test_c1bEz4ZIxKD12kCn',
      'secret_key' => 'sk_test_zSx3B7eZVHivsFQy',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable Key'),
      '#default_value' => $this->configuration['publishable_key'],
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // if (!$form_state->getErrors()) {
    //   $values = $form_state->getValue($form['#parents']);
    //   // Validate the secret key.
    //   $expected_livemode = $values['mode'] == 'live' ? TRUE : FALSE;
    //   if (!empty($values['secret_key'])) {
    //     try {
    //       \Culqi\Culqi::setApiKey($values['secret_key']);
    //       // Make sure we use the right mode for the secret keys.
    //       if (\Culqi\Balance::retrieve()->offsetGet('livemode') != $expected_livemode) {
    //         $form_state->setError($form['secret_key'], $this->t('The provided secret key is not for the selected mode (@mode).', ['@mode' => $values['mode']]));
    //       }
    //     }
    //     catch (\Culqi\Error\Base $e) {
    //       $form_state->setError($form['secret_key'], $this->t('Invalid secret key.'));
    //     }
    //   }
    // }
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['publishable_key'] = $values['publishable_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

     // ksm($payment_storage);
     $txn_id = 'No se validó el pago';
     $authorization_code = 'Authorization';
     $payment_status = '';
     // ksm($request->request->get('txn_id'));}
     $txn_id = $request->request->get('txn_id') ? $request->request->get('txn_id') : $txn_id;
     $authorization_code = $request->request->get('authorization_code') ? 'authorization: '.$request->request->get('authorization_code') : $authorization_code;
     $payment_status = $request->request->get('payment_status') ? $request->request->get('payment_status') : $payment_status;

     // ksm($txn_id);

    $payment = $payment_storage->create([
      'state' => $authorization_code,
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $txn_id, //$request->query->get('txn_id'),
      'remote_state' => $payment_status,
    ]);
    $payment->save();
  }

}
