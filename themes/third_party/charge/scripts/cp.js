
(function($) {

  function convertToSlug(Text,lowerCase)
  {
      var ret = Text.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'_');
      if(lowerCase == false) ret = ret.toUpperCase();

      return ret;
  }


  function iniShortnameSlug() {
      $('.js-shortname').each(function() {
        var $field = $(this);
        var $shortfield = $('#'+$field.data('shortname'));

        $field.on('input', function() {
          if($shortfield.data('raw') == '') $shortfield.val( convertToSlug($field.val()));
        });
      });
  }


  function iniCodeSlug() {
      $('.js-code').each(function() {
        var $field = $(this);
        var $codefield = $('#'+$field.data('code'));

        $field.on('input', function() {
          if($codefield.data('raw') == '') $codefield.val( convertToSlug($field.val(), false));
        });
      });
  }

  function iniDeleteControl() {
    $('.js-delete').each(function() {
      var $link = $(this);

      $link.on('click', function(e) {

        text = 'Are you sure you want to delete this '+$link.data('type')+'?';

        if($link.data('type') == 'Subscription') {
          text = text + '  Any members on this subscription will stay in their current groups, but callbacks from Stripe will be ignored';
        }

        if(!confirm(text)) {
          e.preventDefault();
        }
      })
    });

  }
  /**
   * Initialises the special form interactions
   */
  function iniFormExtras() {

    $('.js-enabler').each(function() {
      var $check = $(this);
      var $tr, rel;
      var inactive_class = 'inactive';
      var mode = 'toggle';

      if($check.hasClass('js-hide')) inactive_class = 'hidden';
      if($check.hasClass('js-select')) mode = 'match';

      if($check.hasClass('initialized')) {
        return;
      }


      $check.addClass('initialized');

      rel = $check.attr('rel');
      $tr = $('.'+rel);

      $check.on('change',function() {

        $tr.each(function(){

          var isDisabled = $(this).hasClass(inactive_class);

          if(mode == 'toggle')
          {
            if(isDisabled) {
              // Enable
              $(this).removeClass(inactive_class);
              $(this).find('input').removeAttr('disabled');
              $(this).find('select').removeAttr('disabled');
            } else {
              // Disable
              $(this).addClass(inactive_class);

              $(this).find('input').attr('disabled', 'disabled');
              $(this).find('select').attr('disabled', 'disabled');
            }
          } else {
            // Check the actual value
            if($check.val() == $(this).attr('rel')) {
              $(this).removeClass(inactive_class);
              $(this).find('input').removeAttr('disabled');
              $(this).find('select').removeAttr('disabled');
            } else {
              // Disable
              $(this).addClass(inactive_class);

              $(this).find('input').attr('disabled', 'disabled');
              $(this).find('select').attr('disabled', 'disabled');
            }
          }

        });


      });
    });
  }


  /**
   * Initialises the 'extended data'.
   */
  function iniExtendedData() {
    $('.extended_data').each(function() {
      var $td = $(this);
      var $body, $link;

      /*
       * Rather rudimentary way of ensuring that the extended data doesn't get
       * initialised twice on the main log page, when the accessory is also
       * present. You'd think EE would be smart enough to not include the same
       * JS file twice, but apparently not.
       */

      if ($td.hasClass('initialized')) {
        return;
      }

      $td.addClass('initialized');

      // Create the new HTML elements.
      $td.wrapInner('<div class="extended_data_body" />');
      $td.append('<a href="#" class="extended_data_toggle">Show Extended</a>');

      // Hide extended data by default.
      $body = $td.find('.extended_data_body');
      $link = $td.find('.extended_data_toggle');

      $body.hide();

      $link.click(function(e) {
        var linkText;
        var isOpen = ($body.css('display').toLowerCase() == 'block');

        $body.slideToggle(250, function() {
          linkText = $body.css('display') == 'block'
            ? 'Hide Extended'
            : 'Show Extended';

          $link.text(linkText);
        });

        e.preventDefault();
      });
    });
  }


  // Start the ball rolling...
  $(document).ready(function() {
    iniExtendedData();
    iniFormExtras();

    iniShortnameSlug();
    iniCodeSlug();
    iniDeleteControl();
  });

})(jQuery);

