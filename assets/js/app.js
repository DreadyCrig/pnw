var hero = document.querySelector('.hero');
var heroFlick = new Flickity( hero, {
  // options
  cellAlign: 'left',
  contain: true,
  pageDots: false,
  imagesLoaded: true
});

$('.card__carousel').flickity({
  // options
  cellAlign: 'left',
  wrapAround: true,
  prevNextButtons: false
});

$('.card__carousel--autoplay').flickity({
  // options
  cellAlign: 'left',
  wrapAround: true,
  prevNextButtons: false,
  autoPlay: 3000
});