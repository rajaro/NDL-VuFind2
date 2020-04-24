/*global VuFind, finna, L, datepicker */
finna.linkedEvents = (function finnaLinkedEvents() {
  function getEvents(params, callback, append) {
    var spinner = $('<i>').addClass('fa fa-spinner fa-spin');
    var app = typeof append !== 'undefined' ? append : false;
    if (append) {
      $('.linked-events-content').append(spinner);
    } else {
      $('.linked-events-content').html(spinner);
    }
    var url = VuFind.path + '/AJAX/JSON?method=getLinkedEvents';
    $.ajax({
      url: url,
      dataType: 'json',
      data: {'params': params.query, 'url': params.url}
    })
      .done(function onGetEventsDone(response) {
        if (response.data) {
          callback(response.data, app);
        } else {
          var err = '<div class="linked-events-noresults infobox">' + VuFind.translate('nohit_heading'); + '</div>'
          $('.linked-events-content').html(err)
        }
        spinner.remove();
      })
      .fail(function getEventsFail(response/*, textStatus, err*/) {
        var err = '<!-- Events could not be loaded';
        if (typeof response.responseJSON !== 'undefined') {
          err += ': ' + response.responseJSON.data;
        }
        err += ' -->';
        $('.linked-events-content').html(err);
      });
  }

  function initEventMap(coordinates) {
    var mapCanvas = $('.linked-events-map');
    var map = finna.map.initMap(mapCanvas, false, {'center': coordinates});
    var icon = L.divIcon({
      className: 'mapMarker',
      iconSize: null,
      html: '<div class="leaflet-marker-icon leaflet-zoom-animated leaflet-interactive"><i class="fa fa-map-marker open" style="position: relative; font-size: 35px;"></i></div>',
      iconAnchor: [10, 35],
      popupAnchor: [0, -36],
      labelAnchor: [-5, -86]
    });
    L.marker(
      [coordinates.lat, coordinates.lng],
      {icon: icon}
    ).addTo(map.map);
    map.map.setZoom(15);
  }

  function getEventContent(id) {
    var params = {};
    params.query = {'id': id};
    getEvents(params, handleSingleEvent);
  }

  var handleSingleEvent = function handleSingleEvent(data) {
    var events = data.events;
    for (var field in events) {
      if (events[field]) {
        if (field === 'position') {
          initEventMap(events[field]);
        }
        if (field === 'endDate' && events.startDate === events.endDate) {
          $('.linked-event-endDate').addClass('hidden');
          continue;
        }
        if ((field === 'startTime' || field === 'endTime') && events.startDate !== events.endDate) {
          continue;
        }
        if (field === 'providerLink') {
          $('.linked-event-providerLink').attr('href', events[field]);
          $('.linked-event-' + field).closest('.linked-event-field').removeClass('hidden');
          continue;
        }
        if (field === 'imageurl') {
          $('.linked-event-image').attr('src', events[field]);
        } if (field === 'keywords') {
          $.each(events[field], function initKeywords(key, val) {
            var html = '<span class="linked-event-keyword">#' + val + '</span>';
            $('.linked-event-keywords').append(html);
          });
        } else {
          $('.linked-event-' + field).append(events[field]);
          $('.linked-event-' + field).closest('.linked-event-field').removeClass('hidden');
        }
      }
    }
    if (data.relatedEvents) {
      $('.related-events').append(data.relatedEvents).removeClass('hidden');
      $('.related-events .linked-event.grid-item').css('flex-basis', '100%');
    }
  }

  var handleMultipleEvents = function handleMultipleEvents(data, append) {
    var container = $('.linked-events-content');
    if (append) {
      container.append(data.html);
    } else {
      container.html(data.html);
    }
    if (data.next !== '') {
      $('.linked-events-next').removeClass('hidden');
      $('.linked-events-next').off('click').click(function onNextClick() {
          var params = {};
          params.url = data.next;
          getEvents(params, handleMultipleEvents, true);
      });
    } else {
      $('.linked-events-next').addClass('hidden');
    }
  }

  function initEventsTabs(initialTitle) {
    var container = $('.linked-events-tabs-container');
    var initial = container.find($('li.nav-item.event-tab#' + initialTitle));
    var initialParams = {};
    initialParams.query = initial.data('params');
    getEvents(initialParams, handleMultipleEvents);
    container.find($('li.nav-item.event-tab')).click(function eventTabClick() {
      if ($(this).hasClass('active')) {
        return false;
      }
      var params = {};
      params.query = $(this).data('params');
      $('li.nav-item.event-tab').removeClass('active').attr('aria-selected', 'false');
      $(this).addClass('active').attr('aria-selected', 'true');
      container.find('.accordion[data-title="' + $(this).id + '"]').addClass('active');
      getEvents(params, handleMultipleEvents);
    }).keyup(function onKeyUp(e) {
      return keyHandler(e);
    });

    var toggleSearchTools = $('.events-searchtools-toggle');
    if (toggleSearchTools[0]) {
      toggleSearchTools.click(function onToggleSeachTools() {
        if ($('.events-searchtools-toggle').hasClass('open')) {
          $('.events-searchtools-container').hide();
          $('.events-searchtools-toggle').removeClass('open');
        } else {
          $('.events-searchtools-container').show();
          $('.events-searchtools-toggle').addClass('open')        
        }
      });
    }

    $('.event-datepicker').datepicker({
      'language': 'fi',
      'format': 'dd.mm.yyyy',
      'weekStart': 1,
      'autoclose': true
    });

    if ($('.events-searchtools-container')[0]) {
      $('.linked-event-search').click(function onSearchClick() {
        var activeParams = $('.event-tab.active').data('params');
        var startDate = $('#event-date-start')[0].value
          ? {'start': $('#event-date-start')[0].value.replace(/\./g, '-')}
          : '';
        var endDate = $('#event-date-end')[0].value
          ? {'end': $('#event-date-end')[0].value.replace(/\./g, '-')}
          : '';
        var textSearch = $('#event-text-search')[0].value
          ? {'text': $('#event-text-search')[0].value}
          : '';

        var newParams = {};
        newParams.query = $.extend(newParams.query, activeParams, startDate, endDate, textSearch);
        getEvents(newParams, handleMultipleEvents);
      })

      $('.event-geolocation').click(function onGeolocationClick() {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(getEventsByGeoLocation);
        }
      });
    }
    initAccordions();
  }

  function initAccordions() {
    $('.event-accordions .accordion').click(function accordionClicked(/*e*/) {
      var accordion = $(this);
      var tabParams = {};
      tabParams.query = accordion.data('params');
      var container = accordion.closest('.linked-events-tabs-container');
      var tabs = accordion.closest('.event-tabs');
      tabs.find('.event-tab').removeClass('active');
      if (toggleAccordion(container, accordion)) {
        getEvents(tabParams, handleMultipleEvents);
      }
      return false;
    }).keyup(function onKeyUp(e) {
      return keyHandler(e);
    });

    function toggleAccordion(container, accordion) {
      var tabContent = container.find('.linked-events-content').detach();
      var searchTools = container.find('.events-searchtools-container').detach();
      var moreButtons = container.find('.linked-events-buttons').detach();
      var toggleSearch = container.find('.events-searchtools-toggle').detach();
      var loadContent = false;
      var accordions = container.find('.event-accordions');
      if (!accordion.hasClass('active') || accordion.hasClass('initial-active')) {
        accordions.find('.accordion.active')
          .removeClass('active')
          .attr('aria-selected', false);

        container.find('.event-tab.active')
          .removeClass('active')
          .attr('aria-selected', false);

        accordions.toggleClass('all-closed', false);

        accordion
          .addClass('active')
          .attr('aria-selected', true);

        container.find('.event-tab[id="' + accordion.data('title') + '"]')
          .addClass('active')
          .attr('aria-selected', true);

        loadContent = true;
      }
      moreButtons.insertAfter(accordion);
      tabContent.insertAfter(accordion);
      searchTools.insertAfter(accordion);
      toggleSearch.insertAfter(accordion);
      accordion.removeClass('initial-active');

      return loadContent;
    }
  }

  function keyHandler(e/*, cb*/) {
    if (e.which === 13 || e.which === 32) {
      $(e.target).click();
      e.preventDefault();
      return false;
    }
    return true;
  }

  function getOrganisationPageEvents(url, callback) {
    var params = {};
    params.url = url;
    getEvents(params, callback);
  }

  function getEventsByGeoLocation(position) {
    var activeParams = $('.event-tab.active').data('params');
    var lat = position.coords.latitude;
    var lon = position.coords.longitude;

    var west = lon - 0.05;
    var east = lon + 0.05;
    var south = lat - 0.05;
    var north = lat + 0.05;
    var bbox = {'west': west, 'south': south, 'east': east, 'north': north};
    var newParams = {}
    newParams.query = $.extend(newParams.query, activeParams, bbox);
    
    getEvents(newParams, handleMultipleEvents);
  }

  var my = {
    initEventsTabs: initEventsTabs,
    getEventContent: getEventContent,
    getOrganisationPageEvents: getOrganisationPageEvents
  };

  return my;
})();
