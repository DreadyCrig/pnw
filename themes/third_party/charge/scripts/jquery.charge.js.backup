(function($) {

    $.fn.charge = function(form, error_container, progress) {

        var $form = $(form);
        var $error_container = $(error_container);
        var $progress = typeof progress !== 'undefined' ? $(progress) : $('.charge_indicator');

        $progress.hide();

        var stripeResponseHandler = function(status, response) {

            if (response.error) {
                    // show the errors on the form
                    $error_container.show().text(response.error.message).addClass('alert').addClass('alert-error');
                    $form.find('button').prop('disabled', false).removeClass('disabled');
                    return false;
            } else {

                var card = {
                    card_token: response['id'],
                    card_last4: response['card']['last4'],
                    card_type: response['card']['brand']
                }

                for (var prop in card) {
                    if(card.hasOwnProperty(prop)){
                        $form.append("<input type='hidden' name=" + prop + " value='" + card[prop] + "'/>");
                    }
                }

                $form.get(0).submit();
            }
        }

        $form.submit(function(event) {
            $error_container.hide();
            $progress.show();

            if($form.find('[name=card_token]').length == 0) {
                event.preventDefault();
                $form.find('button').prop('disabled', true).addClass('disabled');

                try {
                    Stripe.card.createToken($form, stripeResponseHandler);
                } catch(ex) {
                    $error_container.show().text(ex).addClass('alert').addClass('alert-warning');
                    $progress.hide();
                    $form.find('button').prop('disabled', false).removeClass('disabled');
                }
            }
        });

    };

}(jQuery));
