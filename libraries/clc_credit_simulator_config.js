(function($) {
  Drupal.behaviors.clcCreditSimulatorConfig = {
    attach: function(context, settings) {
      var $inputs_percent = $('.field-percent');
      Drupal.inputsPercent($inputs_percent);
    }
  }

  Drupal.inputsPercent = function(elem) {
    elem.on('keypress paste', function(e) {
      // Evitar pegar texto en el campo.
      if (e.type == 'paste') {
        e.preventDefault();
      }
      else {
        // Permitar solo valores númericos.
        if (e.which < 46 || e.which > 59) {
          e.preventDefault();
        }

        // Permitir punto . para decimal.
        if (e.which == 46 && $(this).val().indexOf('.') != -1) {
          e.preventDefault();
        }
      }
    });
  }
})(jQuery);