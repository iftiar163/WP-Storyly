( function () {
  'use strict';

  var cfg     = window.narratoPublications || {};
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
    } ).then( function ( res ) {
      return res.json().then( function ( data ) {
        return { ok: res.ok, status: res.status, data: data };
      } );
    } );
  }

  /* ----------------------------------------------------------
     Tabs
  ---------------------------------------------------------- */
  function initTabs() {
    var tabBtns   = document.querySelectorAll( '.narrato-tab-btn' );
    var tabPanels = document.querySelectorAll( '.narrato-tab-panel' );

    if ( ! tabBtns.length ) return;

    tabBtns.forEach( function ( btn ) {
      btn.addEventListener( 'click', function () {
        var target = btn.getAttribute( 'data-tab' );

        tabBtns.forEach( function ( b ) { b.classList.remove( 'is-active' ); } );
        tabPanels.forEach( function ( p ) { p.classList.remove( 'is-active' ); } );

        btn.classList.add( 'is-active' );
        var panel = document.querySelector( '[data-tab-panel="' + target + '"]' );
        if ( panel ) panel.classList.add( 'is-active' );

        if ( target === 'status' ) {
          loadMySubmissions();
        }
      } );
    } );
  }

  /* ----------------------------------------------------------
     Submit form
  ---------------------------------------------------------- */
  function initSubmitForm() {
    var form = document.getElementById( 'narrato-submit-form' );
    if ( ! form ) return;

    var resultEl = form.querySelector( '.narrato-submit-result' );

    form.addEventListener( 'submit', function ( e ) {
      e.preventDefault();

      var storyId = form.querySelector( '[name="story_id"]' ).value;
      var pubId   = form.querySelector( '[name="pub_id"]' ).value;

      if ( ! storyId || ! pubId ) return;

      resultEl.textContent = cfg.i18n.submitting;
      resultEl.className   = 'narrato-submit-result';

      apiRequest( 'POST', '/publications/' + pubId + '/submit', { story_id: storyId } )
        .then( function ( res ) {
          if ( res.ok ) {
            resultEl.textContent = cfg.i18n.submitted;
            resultEl.className   = 'narrato-submit-result is-success';
            form.reset();
          } else {
            resultEl.textContent = res.data && res.data.error ? res.data.error : cfg.i18n.error;
            resultEl.className   = 'narrato-submit-result is-error';
          }
        } );
    } );
  }

  /* ----------------------------------------------------------
     My submissions list (status tab)
  ---------------------------------------------------------- */
  var mySubmissionsLoaded = false;

  function loadMySubmissions() {
    var container = document.getElementById( 'narrato-my-submissions-list' );
    if ( ! container || mySubmissionsLoaded ) return;

    apiRequest( 'GET', '/my-submissions' ).then( function ( res ) {
      mySubmissionsLoaded = true;
      var submissions = ( res.data && res.data.submissions ) || [];

      if ( ! submissions.length ) {
        container.innerHTML = '<p class="narrato-no-stories">' + cfg.i18n.noSubmissions + '</p>';
        return;
      }

      container.innerHTML = '';
      submissions.forEach( function ( sub ) {
        var row = document.createElement( 'div' );
        row.className = 'narrato-submission-row';

        var noteHtml = sub.editor_note
          ? '<div class="narrato-submission-note">' + escapeHtml( sub.editor_note ) + '</div>'
          : '';

        row.innerHTML =
          '<div class="narrato-submission-info">' +
            '<div class="narrato-submission-title"><a href="' + sub.story_link + '">' + escapeHtml( sub.story_title ) + '</a></div>' +
            '<div class="narrato-submission-meta">' + escapeHtml( sub.publication_name ) + ' · ' + sub.time_ago + ' ago</div>' +
            noteHtml +
          '</div>' +
          '<span class="narrato-submission-status ' + sub.status + '">' + sub.status.replace( '_', ' ' ) + '</span>';

        container.appendChild( row );
      } );
    } );
  }

  function escapeHtml( str ) {
    var div = document.createElement( 'div' );
    div.textContent = str;
    return div.innerHTML;
  }

  /* ----------------------------------------------------------
     Open-submissions toggle (manage tab)
  ---------------------------------------------------------- */
  function initOpenSubmissionsToggle() {
    document.querySelectorAll( '.narrato-open-submissions-toggle' ).forEach( function ( checkbox ) {
      checkbox.addEventListener( 'change', function () {
        var pubId = checkbox.getAttribute( 'data-pub-id' );

        fetch( restUrl.replace( '/narrato/v1', '/wp/v2/narrato_publication/' + pubId ), {
          method  : 'POST',
          headers : {
            'Content-Type' : 'application/json',
            'X-WP-Nonce'   : nonce,
          },
          body: JSON.stringify( {
            meta: { _narrato_pub_open_submissions: checkbox.checked },
          } ),
        } ).catch( function () {
          checkbox.checked = ! checkbox.checked; // revert on failure
        } );
      } );
    } );
  }

  /* ----------------------------------------------------------
     Review queue (publication-reviews.php)
  ---------------------------------------------------------- */
  function initReviewQueue() {
    var container = document.getElementById( 'narrato-review-queue' );
    if ( ! container ) return;

    var pubIds = ( container.getAttribute( 'data-pub-ids' ) || '' )
      .split( ',' )
      .filter( Boolean );

    if ( ! pubIds.length ) {
      container.innerHTML = '<p class="narrato-no-stories">' + cfg.i18n.noSubmissions + '</p>';
      return;
    }

    var allRequests = pubIds.map( function ( pubId ) {
      return apiRequest( 'GET', '/publications/' + pubId + '/submissions?status=pending' );
    } );

    Promise.all( allRequests ).then( function ( results ) {
      var allSubmissions = [];
      results.forEach( function ( res ) {
        if ( res.ok && res.data && res.data.submissions ) {
          allSubmissions = allSubmissions.concat( res.data.submissions );
        }
      } );

      renderReviewQueue( container, allSubmissions );
    } );
  }

  function renderReviewQueue( container, submissions ) {
    if ( ! submissions.length ) {
      container.innerHTML = '<p class="narrato-no-stories">' + cfg.i18n.noSubmissions + '</p>';
      return;
    }

    container.innerHTML = '';

    submissions.forEach( function ( sub ) {
      var row = document.createElement( 'div' );
      row.className = 'narrato-submission-row';
      row.setAttribute( 'data-submission-id', sub.id );

      row.innerHTML =
        '<div class="narrato-submission-info">' +
          '<div class="narrato-submission-title"><a href="' + sub.story_link + '" target="_blank">' + escapeHtml( sub.story_title ) + '</a></div>' +
          '<div class="narrato-submission-meta">' + escapeHtml( sub.submitted_by ) + ' → ' + escapeHtml( sub.publication_name ) + ' · ' + sub.time_ago + ' ago</div>' +
        '</div>' +
        '<div class="narrato-submission-actions">' +
          '<button class="narrato-review-btn approve" data-action="approve">' + cfg.i18n.approve + '</button>' +
          '<button class="narrato-review-btn request_changes" data-action="request_changes">' + cfg.i18n.requestChanges + '</button>' +
          '<button class="narrato-review-btn reject" data-action="reject">' + cfg.i18n.reject + '</button>' +
        '</div>';

      container.appendChild( row );
    } );

    container.querySelectorAll( '.narrato-review-btn' ).forEach( function ( btn ) {
      btn.addEventListener( 'click', function () {
        var action = btn.getAttribute( 'data-action' );
        var row     = btn.closest( '.narrato-submission-row' );
        var subId   = row.getAttribute( 'data-submission-id' );

        if ( action === 'approve' ) {
          submitReview( subId, 'approve', '', row );
        } else {
          openNoteModal( action, function ( note ) {
            submitReview( subId, action, note, row );
          } );
        }
      } );
    } );
  }

  function submitReview( submissionId, action, note, row ) {
    apiRequest( 'POST', '/submissions/' + submissionId + '/review', { action: action, note: note } )
      .then( function ( res ) {
        if ( res.ok ) {
          row.style.opacity = '0.4';
          row.style.pointerEvents = 'none';
          setTimeout( function () {
            row.remove();
          }, 400 );
        } else {
          alert( res.data && res.data.error ? res.data.error : cfg.i18n.error );
        }
      } );
  }

  /* ----------------------------------------------------------
     Note modal (for reject / request changes)
  ---------------------------------------------------------- */
  function openNoteModal( action, onConfirm ) {
    var backdrop = document.createElement( 'div' );
    backdrop.className = 'narrato-note-modal-backdrop';

    var title = action === 'reject' ? cfg.i18n.reject : cfg.i18n.requestChanges;

    backdrop.innerHTML =
      '<div class="narrato-note-modal">' +
        '<h4>' + title + '</h4>' +
        '<textarea placeholder="' + cfg.i18n.addNote + '"></textarea>' +
        '<div class="narrato-note-modal-actions">' +
          '<button class="narrato-review-btn" data-modal-action="cancel">' + cfg.i18n.cancel + '</button>' +
          '<button class="narrato-review-btn ' + action + '" data-modal-action="confirm">' + cfg.i18n.confirm + '</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild( backdrop );

    var textarea = backdrop.querySelector( 'textarea' );

    backdrop.querySelector( '[data-modal-action="cancel"]' ).addEventListener( 'click', function () {
      backdrop.remove();
    } );

    backdrop.querySelector( '[data-modal-action="confirm"]' ).addEventListener( 'click', function () {
      var note = textarea.value.trim();
      backdrop.remove();
      onConfirm( note );
    } );

    backdrop.addEventListener( 'click', function ( e ) {
      if ( e.target === backdrop ) backdrop.remove();
    } );
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  document.addEventListener( 'DOMContentLoaded', function () {
    initTabs();
    initSubmitForm();
    initOpenSubmissionsToggle();
    initReviewQueue();
  } );

} )();