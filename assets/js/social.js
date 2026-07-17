( function () {
  'use strict';

  var cfg     = window.narratoSocial || {};
  var restUrl = cfg.restUrl || '';
  var nonce   = cfg.nonce   || '';

  if ( ! restUrl ) return;

  function apiRequest( method, endpoint, body ) {
    return fetch( restUrl + endpoint, {
      method  : method,
      headers : {
        'Content-Type' : 'application/json',
        'X-WP-Nonce'   : nonce,
      },
      body: body ? JSON.stringify( body ) : undefined,
    } ).then( function ( res ) { return res.json(); } );
  }

  /* ----------------------------------------------------------
     Follow buttons — works for author, topic, story
  ---------------------------------------------------------- */
  function initFollowButtons() {
    document.querySelectorAll( '.narrato-follow-btn[data-follow-type]' ).forEach( function ( btn ) {
      var type     = btn.getAttribute( 'data-follow-type' );
      var objectId = btn.getAttribute( 'data-object-id' );

      btn.addEventListener( 'click', function ( e ) {
        e.preventDefault();

        apiRequest( 'POST', '/follows/toggle', {
          type      : type,
          object_id : objectId,
        } ).then( function ( data ) {
          if ( typeof data.following === 'undefined' ) return;

          if ( data.following ) {
            btn.classList.add( 'is-following' );
            btn.textContent = cfg.i18n.following;
          } else {
            btn.classList.remove( 'is-following' );
            btn.textContent = cfg.i18n.follow;
          }

          // Update any follower count elements on the page
          document.querySelectorAll(
            '.narrato-follower-count[data-follow-type="' + type + '"][data-object-id="' + objectId + '"] strong'
          ).forEach( function ( el ) {
            el.textContent = data.count;
          } );
        } );
      } );
    } );
  }

  /* ----------------------------------------------------------
     Notification bell
  ---------------------------------------------------------- */
  function initNotifications() {
    var wrap = document.querySelector( '.narrato-notif-wrap' );
    if ( ! wrap ) return;

    var bell     = wrap.querySelector( '.narrato-notif-bell' );
    var badge    = wrap.querySelector( '.narrato-notif-badge' );
    var dropdown = wrap.querySelector( '.narrato-notif-dropdown' );

    function loadNotifications() {
      apiRequest( 'GET', '/notifications' ).then( function ( data ) {
        if ( ! data ) return;

        if ( data.unread_count > 0 ) {
          badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }

        dropdown.innerHTML = '';

        if ( ! data.notifications || data.notifications.length === 0 ) {
          var empty = document.createElement( 'div' );
          empty.className = 'narrato-notif-empty';
          empty.textContent = cfg.i18n.noNotifs;
          dropdown.appendChild( empty );
          return;
        }

        data.notifications.forEach( function ( notif ) {
          var a = document.createElement( 'a' );
          a.className = 'narrato-notif-item' + ( notif.is_read ? '' : ' is-unread' );
          a.href = notif.link;
          a.innerHTML = notif.message + '<span class="narrato-notif-time">' + notif.time_ago + ' ago</span>';
          dropdown.appendChild( a );
        } );
      } );
    }

    bell.addEventListener( 'click', function ( e ) {
      e.stopPropagation();
      dropdown.classList.toggle( 'is-open' );

      if ( dropdown.classList.contains( 'is-open' ) ) {
        loadNotifications();
        apiRequest( 'POST', '/notifications/read-all' ).then( function () {
          setTimeout( function () {
            badge.style.display = 'none';
          }, 1500 );
        } );
      }
    } );

    document.addEventListener( 'click', function ( e ) {
      if ( ! wrap.contains( e.target ) ) {
        dropdown.classList.remove( 'is-open' );
      }
    } );

    // Poll unread count every 60s
    loadNotifications();
    setInterval( function () {
      apiRequest( 'GET', '/notifications' ).then( function ( data ) {
        if ( data && data.unread_count > 0 ) {
          badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
          badge.style.display = 'flex';
        }
      } );
    }, 60000 );
  }

  document.addEventListener( 'DOMContentLoaded', function () {
    initFollowButtons();
    initNotifications();
  } );

} )();