// Angular

app = angular.module('app', ['angAccordion', 'ngAnimate', 'ng']);

app.controller('loginCtrl', [
  '$scope',
  function($scope, $route, $http){
    $scope.loginVisible = false;
    $scope.showLogin = function(){
      $scope.loginVisible = true;
    };
    $scope.hideLogin = function(){
      $scope.loginVisible = false;
    };
  }
]);

// Login Intercept
// Step 1: Intercept form submission
$('#loginForm').ajaxForm({
  dataType: 'json',
  success: function(data) {
    // Step 2: If successful, return to page
    if (data.success) {
      location.reload();
    } else {
      swal({
        title: "Login Error",
        text: data.errors.login,
        type: "error",
        html: true
      });
    }
  }
});




var hero = document.querySelector('.hero');
var heroFlick = new Flickity( hero, {
  // options
  cellAlign: 'left',
  contain: true,
  pageDots: false,
  draggable: false
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

  // Inquiry Form Handlers
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
  $('.sponsor-form').ajaxForm({
    dataType: 'json',
    success: processSponsorRequest
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
  function processSponsorRequest(data) {
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
        text: "We have successfuly received your sponsor inquiry",
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

  // Modals
  $(function() {
    $("[id^=modal]").on("change", function() {
      if ($(this).is(":checked")) {
        $("body").addClass("modal-open");
      } else {
        $("body").removeClass("modal-open");
      }
    });

    $(".modal-fade-screen, .modal-close").on("click", function() {
      $(".modal-state:checked").prop("checked", false).change();
    });

    $(".modal-inner").on("click", function(e) {
      e.stopPropagation();
    });
  });

  $('.speaker__name').each(function(e){
    $(this).click(function(e) {
      e.preventDefault();
      $(this).parents('.speaker').find('.modal label').trigger("click");
    })
  });

  $.adaptiveBackground.run();

  // Adapt Headline Text
  $(".fit-text").fitText(1.2, { minFontSize: '20px', maxFontSize: '32px' });
  $(".headline__content h1").fitText(1.2, { minFontSize: '2.625rem', maxFontSize: '54px' });

  // Mask phone numbers
  $("#member_phone").mask("(999) 999-9999");

});
