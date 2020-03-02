/*global VuFind, finna, L */
finna.linkedEvents = (function finnaLinkedEvents() {
  function getEvents(params, callback) {
    var url = VuFind.path + '/AJAX/JSON?method=getLinkedEvents';
    $.ajax({
      url: url,
      dataType: 'json',
      data: {'params': params.query, 'showAll': params.showAll, 'search': params.search}
    })
      .done(function onGetEventsDone(response) {
        if (response.data) {
          callback(response.data);
          return;
        }
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
    for (var field in data) {
      if (data[field]) {
        if (field === 'position') {
          initEventMap(data[field]);
        }
        if (field === 'imageurl') {
          $('.linked-event-image').attr('src', data[field]);
        } else {
          $('.linked-event-' + field).html(data[field]);
          $('.linked-event-' + field).closest('.linked-event-field').removeClass('hidden');
        }
      }
    }
  }

  var handleMultipleEvents = function handleMultipleEvents(data) {
    var container = $('.linked-events-content');
    container.html(data);
  }

  function initEventsTabs(initialTitle) {
    var container = $('.events-tabs');
    var initial = container.find($('li.nav-item.event-tab#' + initialTitle));
    var initialParams = {};
    initialParams.query = initial.data('params');
    initialParams.showAll = container.data('showAll');
    updateMoreLink();
    getEvents(initialParams, handleMultipleEvents);
    container.find($('li.nav-item.event-tab')).click(function eventTabClick() {
      if ($(this).hasClass('active')) {
        return false;
      }
      var params = {};
      params.query = $(this).data('params');
      params.showAll = container.data('showAll');
      $('li.nav-item.event-tab').removeClass('active').attr('aria-selected', 'false');
      $(this).addClass('active').attr('aria-selected', 'true');
      updateMoreLink();
      getEvents(params, handleMultipleEvents);
    }).keyup(function onKeyUp(e) {
      return keyHandler(e);
    });
  }

  function keyHandler(e/*, cb*/) {
    if (e.which === 13 || e.which === 32) {
      $(e.target).click();
      e.preventDefault();
      return false;
    }
    return true;
  }

  function updateMoreLink() {
    var activeTab = $('li.nav-item.event-tab.active').attr('id');
    var moreLink = $('.events-more-link').data('link');
    if (moreLink.indexOf('active=') === -1) {
      moreLink += '&active=' + activeTab + '&showAll=true';
    }
    $('.events-more-link').attr('href', moreLink);
  }

  function getEventsByDate() {
    var activeParams = $('.event-tab.active').data('params');
    var startDate = $('#event-date-start')[0].value
      ? {'start': $('#event-date-start')[0].value}
      : '';
    var endDate = $('#event-date-end')[0].value
      ? {'end': $('#event-date-end')[0].value}
      : '';

    var newParams = {};
    newParams.query = $.extend(newParams.query, activeParams, startDate, endDate);
    newParams.showAll = true;
    getEvents(newParams, handleMultipleEvents);
  }

  function searchEvents() {
    var activeParams = $('.event-tab.active').data('params');
    var startDate = $('#event-date-start')[0].value
      ? {'start': $('#event-date-start')[0].value}
      : '';
    var endDate = $('#event-date-end')[0].value
      ? {'end': $('#event-date-end')[0].value}
      : '';
    
    var textSearch = $('#event-text-search')[0].value
    ? {'text': $('#event-text-search')[0].value}
    : '';

    var newParams = {};
    newParams.query = $.extend(newParams.query, activeParams, startDate, endDate, textSearch);
    newParams.showAll = true;
    getEvents(newParams, handleMultipleEvents);
  }

  function initEventGeoLocation() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(getEventsByGeoLocation);
    } else {
    }
  }

  function getEventsByGeoLocation(position) {
    var activeParams = $('.event-tab.active').data('params');
    activeParams.showAll = true;
    var lat = position.coords.latitude;
    var lon = position.coords.longitude;

    var west = lon - 0.05;
    var east = lon + 0.05;
    var south = lat - 0.05;
    var north = lat + 0.05;
    var newParams = {}
    newParams.query = $.extend(newParams.query, activeParams, {'bbox': {west, south, east, north}});
    
    getEvents(newParams, handleMultipleEvents);
  }

  var my = {
    getEventsByDate: getEventsByDate,
    initEventsTabs: initEventsTabs,
    getEventContent: getEventContent,
    searchEvents: searchEvents,
    initEventGeoLocation: initEventGeoLocation
  };

  return my;
})();
