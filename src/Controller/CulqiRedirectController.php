<?php

namespace Drupal\commerce_culqi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;



use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase;

use Drupal\commerce_order\Entity\Order;
define('RESPONSE_CULQI_SUCCESS', 'AUT0000');

// use Drupal\Core\Controller\ControllerBase;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\Core\Entity\Query\QueryFactory;

/**
 * This is a dummy controller for mocking an off-site gateway.
 */
class CulqiRedirectController implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new CulqiRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function post111( Request $request ) {
    
    // This condition checks the `Content-type` and makes sure to 
    // decode JSON string from the request body into array.
     $data = $request->getContent();
     // ksm($request->getParameters());
    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
      $data = json_decode( $request->getContent(), TRUE );
      // $request->request->replace( is_array( $data ) ? $data : [] );
    }
 
    // $response['data'] = $data;
    // $response['method'] = 'POST';

    return new JsonResponse( $data );
  }


  /**
   * {@inheritdoc}
   */
  
 public function validate_charge($param) {
  if($param['outcome']->code) {
    if($param['outcome']->code == RESPONSE_CULQI_SUCCESS) {
      return true;
    }
  }
  return false;
 }

/**
 * Callback method which accepts POST.
 *
 * @throws \Drupal\commerce\Response\NeedsRedirectException
 */
public function post122() {

  $param['outcome']->code = "AUT000011";

$response_data['culqi'] = RESPONSE_CULQI_SUCCESS;
   $response_data['demo'] = $this->validate_charge($param);
 

     $response = new CacheableJsonResponse($response_data);
   
   // $response->addCacheableDependency($cache_metadata);

   return $response;

}

  /**
   * Callback method which accepts POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function post() {
    // ksm( $this->currentRequest->request);
    // ksm( $this->currentRequest->request->all());
    // ksm( $this->currentRequest->request->get('source_id'));
    // $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    
    // ksm($payment_storage);
     // ksm( $this->currentRequest->attributes->get('order'));
     $response_data = [];

    if($this->currentRequest->attributes->get('order')){
      $order_id = $this->currentRequest->attributes->get('order');
      $order = Order::load($order_id);

      if ($order->get('payment_gateway')->isEmpty()) {
        return;
      }
      $payment = $order->get('payment_gateway')->entity;
      $payment_configuration = $payment->get("configuration");
      $secret_key = $payment_configuration['secret_key'];


      $Culqi = new \Culqi\Culqi(array('api_key' => $secret_key));
      $response_for_culqi =  $this->currentRequest->request->all();

      $data = array(
              "amount" => $response_for_culqi['amount'],
              "capture" => true,
              "currency_code" => $response_for_culqi['currency_code'],
              "email" => $response_for_culqi['email'],
              "source_id" => $response_for_culqi['source_id'],
              "antifraud_details" => array(
                "address" => $response_for_culqi['client']['address'] ? $response_for_culqi['client']['address'] : 'NN',
                "address_city" => $response_for_culqi['client']['city'] ? $response_for_culqi['client']['city'] : 'NN',
                "country_code" => "PE",
                "first_name" => $response_for_culqi['client']['name'] ? $response_for_culqi['client']['name'] : 'NN',
                "last_name" => $response_for_culqi['client']['last_name'] ? $response_for_culqi['client']['last_name'] : 'NN',
                // "phone_number" => $response_for_culqi['client']['phone'] ? $response_for_culqi['client']['phone'] : 'NN',
            ),
          );
      $response_data['validate'] = false;
       try {
            // Creando Cargo a una tarjeta
            $charge = $Culqi->Charges->create($data);

            // echo json_encode($charge);
            // exit();

            $response_data['param'] = (array)$charge;

            $param = (array)$charge;
            if($this->validate_charge($param)) { //if charge created
              $response_data['validate'] = true;
              $response_data['txn_id'] = $param['id'];
              $response_data['authorization_code'] = $param['authorization_code'];
              $response_data['payment_status'] = 'venta_exitosa';//$param['outcome']['type'];
              $response_data['message'] = $param['outcome']->merchant_message;
            }else {
              $response_data['validate'] = false;
              $response_data['message'] = $param['outcome']->merchant_message;
            }
            
            $response_data['charge'] = $param;
            // echo json_encode($response_data);
            // exit();

          } catch (Exception $e) {
            $response_data['error'] = $e->getMessage();
          }

      // $response_data['secret_key'] = $secret_key ;
      echo json_encode($response_data);
      exit();

    }
    else {
      $response_data['validate'] = false;
    }
    echo json_encode($response_data);
    exit();
    // $response = new CacheableJsonResponse($response_data);
    // return $response; 

  }


/*
  if($_POST) {
    header('Content-Type: application/json');
    $path = drupal_get_path('module', 'commerce_culqi');
    include_once dirname(__FILE__).'/vendor/autoload.php';

    if($_POST['token'] == 'cancel') {
      commerce_order_status_update($order, 'canceled');
      $result['message'] = 'ok';
      echo json_encode($result);
      exit();
    }
    else {
        $payment = commerce_payment_method_instance_load($order->data['payment_method']);
        $settings = $payment['settings'];
        $description = t($settings['py_description'], array(
        '@order_id' => $order->order_id,
        ));

        $culqi = new Culqi\Culqi(array('api_key' => $settings['py_api_key']));

        $data = array(
              "amount" => $order->commerce_order_total['und'][0]['amount'],
              "capture" => true,
              "currency_code" => $order->commerce_order_total['und'][0]['currency_code'],
              "description" => $description,
              "email" => $order->mail,
              "installments" => (int)$_POST["installments"],
              "source_id" => $_POST["token"]
          );

          try {
            // Creando Cargo a una tarjeta
            $charge = $culqi->Charges->create($data);

            $param = (array)$charge;
            if(validate_charge($param)) {
              commerce_culqi_send_pay($order, $param);
            }
            // Response
            echo json_encode($charge);
            exit();
          } catch (Exception $e) {

            echo json_encode($e->getMessage());

          }
    }
  }
  else {
    return drupal_not_found();
  }
*/


  /**
   * Callback method which reacts to GET from a 302 redirect.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function on302() {
    $cancel = $this->currentRequest->query->get('cancel');
    $return = $this->currentRequest->query->get('return');
    $total = $this->currentRequest->query->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

}


// https://www.zonarutoppuden.com/2014/01/naruto-shippuden-117.html