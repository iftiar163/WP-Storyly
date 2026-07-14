( function () {
  'use strict';

  var cfg     = window.narratoEngagement || {};
  var postId  = cfg.postId  || 0;
  var restUrl = cfg.restUrl || '';
  var nonce   = cfg.nonce   || '';

  if ( ! postId || ! restUrl ) return;

  /* ----------------------------------------------------------
     Utility: REST request
  ---------------------------------------------------------- */
  function apiRequest( method, endpoint, body ) {
    return fetch( restUrl + endpoint, {
      method  : method,
      headers : {
        'Content-Type' : 'application/json',
        'X-WP-Nonce'   : nonce,
      },
      body: body ? JSON.stringify( body ) : undefined,
    } ).then( function ( res ) {
      return res.json();
    } );
  }

  /* ----------------------------------------------------------
     Utility: Toast notification
  ---------------------------------------------------------- */
  var toastEl = null;
  var toastTimer = null;

  function showToast( message ) {
    if ( ! toastEl ) {
      toastEl = document.createElement( 'div' );
      toastEl.className = 'narrato-toast';
      document.body.appendChild( toastEl );
    }
    toastEl.textContent = message;
    toastEl.classList.add( 'is-visible' );
    clearTimeout( toastTimer );
    toastTimer = setTimeout( function () {
      toastEl.classList.remove( 'is-visible' );
    }, 2500 );
  }

  /* ----------------------------------------------------------
     Utility: Clap burst animation
  ---------------------------------------------------------- */
  function showClapBurst( btn, count ) {
    var burst = document.createElement( 'span' );
    burst.className   = 'narrato-clap-burst';
    burst.textContent = '+' + count;
    burst.style.position = 'absolute';
    btn.style.position   = 'relative';
    btn.appendChild( burst );
    setTimeout( function () {
      if ( burst.parentNode ) burst.parentNode.removeChild( burst );
    }, 800 );
  }

  /* ----------------------------------------------------------
     Sidebar visibility
  ---------------------------------------------------------- */
  function initSidebarVisibility( sidebar ) {
    var content = document.querySelector( '.narrato-content' );
    if ( ! content ) return;

    function check() {
      var rect = content.getBoundingClientRect();
      var inView = rect.top < window.innerHeight && rect.bottom > 100;
      if ( inView ) {
        sidebar.classList.add( 'is-visible' );
      } else {
        sidebar.classList.remove( 'is-visible' );
      }
    }

    window.addEventListener( 'scroll', check, { passive: true } );
    check();
  }

  /* ----------------------------------------------------------
     Clap logic
  ---------------------------------------------------------- */
  var clapPending   = 0;
  var clapTimer     = null;
  var currentClaps  = 0;
  var maxClaps      = cfg.maxClaps || 50;

  function initClaps( btn, countEl ) {
    // Load initial state
    apiRequest( 'GET', '/claps/' + postId ).then( function ( data ) {
      if ( data && typeof data.total !== 'undefined' ) {
        currentClaps = data.user_claps || 0;
        updateClapUI( btn, countEl, data.total, currentClaps );
      }
    } );

    btn.addEventListener( 'click', function () {
      if ( currentClaps >= maxClaps ) {
        showToast( cfg.i18n.maxReached );
        return;
      }

      clapPending++;
      currentClaps++;
      showClapBurst( btn, 1 );
      btn.classList.add( 'is-clapping' );
      setTimeout( function () { btn.classList.remove( 'is-clapping' ); }, 300 );

      // Update count display optimistically
      var displayed = parseInt( countEl.textContent, 10 ) || 0;
      countEl.textContent = displayed + 1;

      if ( currentClaps >= maxClaps ) {
        btn.classList.add( 'is-maxed' );
        showToast( cfg.i18n.maxReached );
      }

      // Debounce — batch claps and send after 1s of inactivity
      clearTimeout( clapTimer );
      clapTimer = setTimeout( function () {
        var toSend = clapPending;
        clapPending = 0;
        apiRequest( 'POST', '/claps/' + postId, { count: toSend } )
          .then( function ( data ) {
            if ( data && typeof data.total !== 'undefined' ) {
              countEl.textContent = data.total;
              // Sync all clap counts on page
              document.querySelectorAll( '.narrato-clap-count' ).forEach( function ( el ) {
                el.textContent = data.total;
              } );
            }
          } );
      }, 1000 );
    } );
  }

  function updateClapUI( btn, countEl, total, userClaps ) {
    countEl.textContent = total;
    if ( userClaps >= maxClaps ) {
      btn.classList.add( 'is-maxed' );
    }
  }

  /* ----------------------------------------------------------
     Bookmark logic
  ---------------------------------------------------------- */
  function initBookmarks( btn, labelEl ) {
    // Load initial state
    apiRequest( 'GET', '/bookmarks' ).then( function ( data ) {
      if ( data && data.bookmarks ) {
        var isBookmarked = data.bookmarks.indexOf( postId ) !== -1;
        setBookmarkState( btn, labelEl, isBookmarked );
      }
    } );

    btn.addEventListener( 'click', function () {
      apiRequest( 'POST', '/bookmarks/' + postId ).then( function ( data ) {
        if ( data && typeof data.bookmarked !== 'undefined' ) {
          setBookmarkState( btn, labelEl, data.bookmarked );
          showToast(
            data.bookmarked ? cfg.i18n.bookmarked : cfg.i18n.bookmark
          );
        }
      } );
    } );
  }

  function setBookmarkState( btn, labelEl, isBookmarked ) {
    if ( isBookmarked ) {
      btn.classList.add( 'is-bookmarked' );
      if ( labelEl ) labelEl.textContent = cfg.i18n.bookmarked;
      btn.setAttribute( 'aria-label', cfg.i18n.bookmarked );
    } else {
      btn.classList.remove( 'is-bookmarked' );
      if ( labelEl ) labelEl.textContent = cfg.i18n.bookmark;
      btn.setAttribute( 'aria-label', cfg.i18n.bookmark );
    }
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  document.addEventListener( 'DOMContentLoaded', function () {
    var sidebars = document.querySelectorAll( '.narrato-engagement-sidebar, .narrato-engagement-inline' );

    sidebars.forEach( function ( sidebar ) {
      var clapBtn  = sidebar.querySelector( '.narrato-clap-btn' );
      var clapCount = sidebar.querySelector( '.narrato-clap-count' );
      var bookmarkBtn  = sidebar.querySelector( '.narrato-bookmark-btn' );
      var bookmarkLabel = sidebar.querySelector( '.narrato-bookmark-label' );

      if ( clapBtn && clapCount ) {
        initClaps( clapBtn, clapCount );
      }

      if ( bookmarkBtn ) {
        initBookmarks( bookmarkBtn, bookmarkLabel );
      }

      if ( sidebar.classList.contains( 'narrato-engagement-sidebar' ) ) {
        initSidebarVisibility( sidebar );
      }
    } );
  } );

} )();