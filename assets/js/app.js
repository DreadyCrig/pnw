// Angular

app = angular.module('app', ['angAccordion', 'ngAnimate']);


var hero = document.querySelector('.hero');
var heroFlick = new Flickity( hero, {
  // options
  cellAlign: 'left',
  contain: true,
  pageDots: false,
  imagesLoaded: true
});

$(function() {
  // Carousels
  $('.card__carousel').flickity({
    // options
    cellAlign: 'left',
    prevNextButtons: false
  });

  $('.card__carousel--autoplay').flickity({
    // options
    cellAlign: 'left',
    prevNextButtons: false,
    autoPlay: 3000
  });

  // Newsletter SignUp
  $('.newsletter__form').ajaxForm({
    target: ".newsletter-response"
  });
  $('.contact-form').ajaxForm({
    dataType: 'json',
    success: processContactRequest
  });
  $('.rsvp-form').ajaxForm({
    dataType: 'json',
    success: processRsvpRequest
  });

  function processContactRequest(data) {
    if (data.success == false) {
      swal({
        title: "Oops, something went wrong!",
        text: "Please ensure that you've entered your full name, <br>and a valid email address.",
        type: "error",
        html: true
      });

    } else {
      swal({
        title: "Thank You",
        text: "We have successfuly received your contact request",
        type: "success"
      });
    }
  }
  function processRsvpRequest(data) {
    if (data.success == false) {
      swal({
        title: "Oops, something went wrong!",
        text: "Please ensure that you've entered your full name, a valid email address, and a phone number.",
        type: "error",
        html: true
      });

    } else {
      swal({
        title: "You've been booked!",
        text: "Thank you for taking the time to RSVP to this event.",
        type: "success"
      });
    }
  }

});