<?php

namespace Drupal\commerce_culqi;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;

/**
 * Translates Culqi exceptions and errors into Commerce exceptions.
 */
class ErrorHelper {

  /**
   * Translates Culqi exceptions into Commerce exceptions.
   *
   * @param \Culqi\Error\Base $exception
   *   The Culqi exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(\Culqi\Error\Base $exception) {
    if ($exception instanceof \Culqi\Error\Card) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      if ($exception->getCulqiCode() == 'card_declined' && $exception->getDeclineCode() == 'card_not_supported') {
        // Culqi only supports Visa/MasterCard/Amex for non-USD transactions.
        // @todo Find a better way to communicate this to the customer.
        $message = t('Your card is not supported. Please use a Visa, MasterCard, or American Express card.');
        drupal_set_message($message, 'warning');
        throw new HardDeclineException($message);
      }
      else {
        throw new DeclineException('We encountered an error processing your card details. Please verify your details and try again.');
      }
    }
    elseif ($exception instanceof \Culqi\Error\RateLimit) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      throw new InvalidRequestException('Too many requests.');
    }
    elseif ($exception instanceof \Culqi\Error\InvalidRequest) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      throw new InvalidRequestException('Invalid parameters were supplied to Culqi\'s API.');
    }
    elseif ($exception instanceof \Culqi\Error\Authentication) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      throw new AuthenticationException('Culqi authentication failed.');
    }
    elseif ($exception instanceof \Culqi\Error\ApiConnection) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      throw new InvalidResponseException('Network communication with Culqi failed.');
    }
    elseif ($exception instanceof \Culqi\Error\Base) {
      \Drupal::logger('commerce_culqi')->warning($exception->getMessage());
      throw new InvalidResponseException('There was an error with Culqi request.');
    }
    else {
      throw new InvalidResponseException($exception->getMessage());
    }
  }

  /**
   * Translates Culqi errors into Commerce exceptions.
   *
   * @todo
   *   Make sure if this is really needed or handleException cover all
   *   possible errors.
   *
   * @param object $result
   *   The Culqi result object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleErrors($result) {
    $result_data = $result->__toArray();
    if ($result_data['status'] == 'succeeded') {
      return;
    }

    // @todo: Better handling for possible Culqi errors.
    if (!empty($result_data['failure_code'])) {
      $failure_code = $result_data['failure_code'];
      // https://culqi.com/docs/api?lang=php#errors
      // Validation errors can be due to a module error (mapped to
      // InvalidRequestException) or due to a user input error (mapped to
      // a HardDeclineException).
      $hard_decline_codes = ['processing_error', 'missing', 'card_declined'];
      if (in_array($failure_code, $hard_decline_codes)) {
        throw new HardDeclineException($result_data['failure_message'], $failure_code);
      }
      else {
        throw new InvalidRequestException($result_data['failure_message'], $failure_code);
      }
    }
  }

}
