( function () {
  'use strict';

  var cfg     = window.narratoAccount || {};
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

  function escapeHtml( str ) {
    var div = document.createElement( 'div' );
    div.textContent = str;
    return div.innerHTML;
  }

  /* ----------------------------------------------------------
     Profile edit form
  ---------------------------------------------------------- */
  function initProfileForm() {
    var form = document.getElementById( 'narrato-account-form' );
    if ( ! form ) return;

    var resultEl = form.querySelector( '.narrato-account-save-result' );

    form.addEventListener( 'submit', function ( e ) {
      e.preventDefault();

      var displayName = form.querySelector( '[name="display_name"]' ).value;
      var bio         = form.querySelector( '[name="bio"]' ).value;

      apiRequest( 'POST', '/account/profile', {
        display_name : displayName,
        bio          : bio,
      } ).then( function ( data ) {
        if ( data && data.success ) {
          resultEl.textContent = cfg.i18n.saved;
          setTimeout( function () { resultEl.textContent = ''; }, 3000 );
        } else {
          resultEl.textContent = cfg.i18n.error;
        }
      } );
    } );
  }

  /* ----------------------------------------------------------
     Recent notifications (reuses existing notifications endpoint)
  ---------------------------------------------------------- */
  function loadNotifications() {
    var container = document.getElementById( 'narrato-account-notifications' );
    if ( ! container ) return;

    apiRequest( 'GET', '/notifications' ).then( function ( data ) {
      var notifications = ( data && data.notifications ) || [];

      if ( ! notifications.length ) {
        container.innerHTML = '<p class="narrato-no-stories">' + cfg.i18n.noNotifs + '</p>';
        return;
      }

      container.innerHTML = '';
      notifications.slice( 0, 10 ).forEach( function ( n ) {
        var item = document.createElement( 'div' );
        item.className = 'narrato-account-notif-item';
        item.innerHTML =
          '<a href="' + n.link + '">' + escapeHtml( n.message ) + '</a>' +
          '<span class="narrato-account-notif-time">' + n.time_ago + ' ago</span>';
        container.appendChild( item );
      } );
    } );
  }

  document.addEventListener( 'DOMContentLoaded', function () {
    initProfileForm();
    loadNotifications();
  } );

} )();