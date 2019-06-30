/**
 * @file
 * Javascript to generate Culqi token in PCI-compliant way.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the commerceCulqiForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop object cardNumber
   *   Culqi card number element.
   * @prop object cardExpiry
   *   Culqi card expiry element.
   * @prop object cardCvc
   *   Culqi card cvc element.
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commerceCulqiForm behavior.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the commerceCulqiForm behavior.
   *
   * @see Drupal.commerceCulqi
   */

  Drupal.behaviors.commerceCulqiForm = {
    cardNumber: null,
    cardExpiry: null,
    cardCvc: null,

    anotherFunction: function() {
      // console.log("funcion---");
    },

    attach: function (context) {
      var self = this;
      if (!drupalSettings.commerceCulqi || !drupalSettings.commerceCulqi.publishableKey) {
        return;
      }

       var url_post = drupalSettings.commerceCulqi.url_post;
       Culqi.publicKey = drupalSettings.commerceCulqi.publishableKey;
      //  var amount =  drupalSettings.commerceCulqi.amount;
       Culqi.options({  style : { logo : "https://www.geomatica.pe/logoCulqui.png"  } });
       Culqi.settings({
            title: drupalSettings.commerceCulqi.title,
            currency: drupalSettings.commerceCulqi.currency,
            description: drupalSettings.commerceCulqi.description,
            amount: drupalSettings.commerceCulqi.amount
        });

       $('.payment-culqi').on('click', function (e) {
         
         Culqi.open();
         e.preventDefault();
      });

      $( document ).ready(function() {
        if(Culqi) {
          Culqi.open();
        }
      });
      

       function culqi() {
        // console.log("load culqi fun xxxxxxxxx");

        $(document).ajaxStart(function(){
              run_waitMe();
        });
        console.log("lueggo-->", Culqi)
        if (Culqi.token) { // ¡Token creado exitosamente!
            // Get the token ID:
            var token = Culqi.token.id;
            // console.log("Culqi", Culqi);

             // var url = "/commerce_culqi/dummy_redirect_post?_format_json";
        
             var  data = {
                    "source_id": Culqi.token.id,
                    "amount": drupalSettings.commerceCulqi.amount,
                    "currency_code": drupalSettings.commerceCulqi.currency,
                    "email": Culqi.token.email,
                    "return": drupalSettings.commerceCulqi.return,
                    "client": drupalSettings.commerceCulqi.client
                  };

                jQuery.post(url_post, data).done(function(response){
                  // console.log("swss response",JSON.parse(response));
                   response = JSON.parse(response);
                      if(response['validate']) {
                        var data = {
                          txn_id: response['txn_id'],
                          authorization_code: response['authorization_code'],
                          payment_status: response['payment_status'],
                        }
                        setTimeout(function() {
                          jQuery.redirect(drupalSettings.commerceCulqi.return,data, "POST");   
                        }, 100);
                      }
                      else {
                        $('body').waitMe('hide');
                        alert("Error al procesar el pago, vuelva a intentarlo por favor.");
                      }
                })
                .fail(function(res) {
                        $('body').waitMe('hide');
                        alert("Error al procesar el pago, vuelva a intentarlo por favor.");
                })


        } else { // ¡Hubo algún problema!
            // Mostramos JSON de objeto error en consola
            console.log("culqi error",Culqi);
            // alert(Culqi.error.mensaje);
        }
    };

    // function demouno(){

    //   console.log("Culqi",Culqi);
    // }

     window.culqi = culqi;


     
    },

    detach: function (context, settings, trigger) {
      // if (trigger !== 'unload') {
      //   return;
      // }
      // var self = this;
      // ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(function (i) {
      //   if (self[i] && self[i].length > 0) {
      //     self[i].unmount();
      //     self[i] = null;
      //   }
      // });
      // var $form = $('.Culqi-form', context).closest('form');
      // if ($form.length === 0) {
      //   return;
      // }
      // $form.off('submit.commerce_Culqi');
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceCulqiError: function (message) {
      return $('<div class="messages messages--error"></div>').html(message);
    }
  });

})(jQuery, Drupal, drupalSettings);


function run_waitMe(message){
      jQuery('body').waitMe({
        effect: 'orbit',
        text: message ? message : 'Procesando pago...',
        bg: 'rgba(255,255,255,0.7)',
        color:'#28d2c8'
      });
}
