(function ($) {

  Drupal.behaviors.clcCreditSimulator = {
    attach: function(context, settings) {
      var simulator_config = settings.simulator_config;
      var c1 = 'active';
      // Using once() with more complexity.
      $('#block-creditsimulator', context).once('price_format').each(function() {
        // To format price value.
        let $motorcicly_price = $('#block-creditsimulator h4.field-price span');
        let m_price = parseInt($motorcicly_price.text());
        m_price = Drupal.formatCurrency(m_price);
        $motorcicly_price.text(m_price);
      });
      // Active steps in form.
      $('.next-step').click(function(e) {
        e.preventDefault();
        var validate = true;
        if ($(this).is('#edit-btn-step-first')) {
          let total_finance = parseInt(simulator_config.amount);
          let initial_fee = Drupal.removeSymbolsCurrency($('#edit-fee').val());
          initial_fee = parseInt(initial_fee);
          if (isNaN(initial_fee)) {
            initial_fee = 0;
          }

          if (initial_fee > total_finance) {
            validate = false;
          }
        }

        if (validate) {
          let $parent = $(this).parents('fieldset');
          let $next_fieldset = $parent.next();

          $parent.removeClass(c1);
          $next_fieldset.addClass(c1);
        }
      });
      //close modal in mobile
      $(".close_modal").click(
        function(e){
          e.preventDefault();
          $("#block-creditsimulator").removeClass('active'); 
          $(".images-preview").toggleClass("h_cotizador");
          $(".main-image").toggle();
          $(".container-images-miniature").toggle();             
        }
      );
      
      //back event fieldset
      $('.retryCot').click(function(e) {
        e.preventDefault();
        var $parent = $(this).parents('fieldset');
        var $prev_fieldset = $parent.prev();

        $parent.removeClass(c1);
        $prev_fieldset.addClass(c1);
      });

      // Active modals for item.
      $('.elem-trigger').click(function() {
        var container = $(this).data('container');

        $('.item-modal').not($(container)).removeClass(c1);
        $(container).toggleClass(c1);
      });

      $('#edit-btn-step-first').click(function(e) {
        e.preventDefault();
        var total_finance = parseInt(simulator_config.amount),
          iva = parseFloat(simulator_config.iva),
          fee = parseFloat(simulator_config.fee),
          aval = parseFloat(simulator_config.aval),
          interest = parseFloat(simulator_config.interest),
          credit_insurance = parseFloat(simulator_config.credit_insurance),
          amount_insurance = parseFloat(simulator_config.amount_insurance);
          var months = parseInt($('#edit-payments').val());
          var initial_fee = Drupal.removeSymbolsCurrency($('#edit-fee').val());
          initial_fee = parseInt(initial_fee);
          if (isNaN(initial_fee)) {
            initial_fee = 0;
          }

        $('span.error-field').remove();
        if (initial_fee < total_finance) {
          total_finance = total_finance - initial_fee;

          // Segun formula.
          let fga = total_finance * (iva + 1) * aval; // FGA.
          let tf = fga + total_finance; // Total to finance.
          let mi = amount_insurance * simulator_config.amount; // Insurance motorcycle.
          let li = credit_insurance * tf; // Insurance life.
          // Cuota antes de seguro.
          let cas = (interest * Math.pow(1 + interest, months)) *  tf / (Math.pow(1 + interest, months) - 1);
          let total_fee = cas + li + mi;

          // Formated values.
          let finance_amount_formated = Drupal.formatCurrency(tf);
          let total_fee_formated = Drupal.formatCurrency(total_fee);

          $('.item-fga .item-value').text(Drupal.formatCurrency(fga));
          $('.item-fee-numbers .item-value').text(months);
          $('.item-finance-amount .item-value').text(finance_amount_formated);
          $('.item-life-insurance .item-value').text(Drupal.formatCurrency(li));
          $('.item-moto-insurance .item-value').text(Drupal.formatCurrency(mi));
          $('.item-payment-fee .item-value').text(total_fee_formated);

          // Set values in hidden fields.
          $('.finance-amount').val(finance_amount_formated);
          $('.payment-fee').val(total_fee_formated);
        }
        else {
          var $validate_field = $('<span>').addClass('error-field');
          $validate_field.text('La cuota inicial no puede superar el valor del vehículo');
          $('.form-item-fee').append($validate_field);
          $('#edit-fee').focus();
        }
      });

      // To Format price in fee initial.
      $('#edit-fee').keyup(function(e) {
        let fee = Drupal.removeSymbolsCurrency($(this).val());
        fee = Drupal.formatCurrency(fee);

        $(this).val(fee);
      });

      // Fields numeric.
      $('.field-numeric').on('keypress paste', function(e) {
        if (e.type == 'paste') {
          e.preventDefault();
        }
        if(e.which < 46 || e.which > 59) {
          e.preventDefault();
        }
        if(e.which == 46 && $(this).val().indexOf('.') != -1) {
          e.preventDefault();
        }
      });
    }
  }

  Drupal.formatCurrency = function(value) {
    value = Math.round(value);

    // Concat $ symbol to result.
    return '$' + value.toLocaleString('de-DE');
  }

  Drupal.removeSymbolsCurrency = function(value) {
    // Remove $ symbol.
    if (value.indexOf('$') === 0) {
      let lengthstr = value.length;
      value = value.substr(1, lengthstr);
    }

    // Remove . points.
    value = value.replace(/\./g, '');

    return value;
  }
})(jQuery);


