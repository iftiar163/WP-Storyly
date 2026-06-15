( function () {
  'use strict';

  var bar = document.getElementById( 'narrato-progress' );
  if ( ! bar ) return;

  var content = document.querySelector( '.narrato-content' );
  if ( ! content ) return;

  function updateProgress() {
    var contentTop    = content.getBoundingClientRect().top + window.scrollY;
    var contentBottom = contentTop + content.offsetHeight;
    var windowBottom  = window.scrollY + window.innerHeight;
    var total         = contentBottom - contentTop;
    var scrolled      = windowBottom - contentTop;
    var pct           = Math.min( Math.max( scrolled / total * 100, 0 ), 100 );
    bar.style.width   = pct.toFixed( 2 ) + '%';
  }

  window.addEventListener( 'scroll', updateProgress, { passive: true } );
  updateProgress();
} )();