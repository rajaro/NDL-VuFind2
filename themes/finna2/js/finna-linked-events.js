/*global VuFind, finna, L */
finna.linkedEvents = (function finnaLinkedEvents() {
  function getEvents(params, callback, append, container) {
    var spinner = $('<i>').addClass('fa fa-spinner fa-spin');
    if (append) {
      container.find($('.linked-events-content')).append(spinner);
    } else {
      container.find($('.linked-events-content')).html(spinner);
    }
    var url = VuFind.path + '/AJAX/JSON?method=getLinkedEvents';
    $.ajax({
      url: url,
      dataType: 'json',
      data: {'params': params.query, 'url': params.url}
    })
      .done(function onGetEventsDone(response) {
        if (response.data) {
          callback(response.data, append, container);
        } else {
          var err = '<div class="linked-events-noresults infobox">' + VuFind.translate('nohit_heading') + '</div>'
          container.find($('.linked-events-content')).html(err)
          container.find($('.linked-events-next')).addClass('hidden');
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
    var container = $('.linked-event-content');
    getEvents(params, handleSingleEvent, false, container);
  }

  var handleSingleEvent = function handleSingleEvent(data) {
    var events = data.events;
    for (var field in events) {
      if (events[field]) {
        if (field === 'location') {
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

  var handleMultipleEvents = function handleMultipleEvents(data, append, container) {
    var content = container.find($('.linked-events-content'));
    if (append) {
      content.append(data.html);
    } else {
      content.html(data.html);
    }
    if (data.next !== '') {
      container.find($('.linked-events-next')).removeClass('hidden');
      container.find($('.linked-events-next')).off('click').click(function onNextClick() {
        var params = {};
        params.url = data.next;
        getEvents(params, handleMultipleEvents, true, container);
      });
    } else {
      container.find($('.linked-events-next')).addClass('hidden');
    }
  }

  function initEventsTabs(id) {
    var container = $('.linked-events-tabs-container[id="' + id + '"]');
    var initial = container.find($('li.nav-item.event-tab.active'));
    var limit = {'page_size': container.data('limit')};
    var initialParams = {};
    initialParams.query = $.extend(initial.data('params'), limit);
    getEvents(initialParams, handleMultipleEvents, false, container);
    container.find($('li.nav-item.event-tab')).click(function eventTabClick() {
      if ($(this).hasClass('active')) {
        return false;
      }
      var params = {};
      params.query = $.extend($(this).data('params'), limit);
      container.find($('li.nav-item.event-tab')).removeClass('active').attr('aria-selected', 'false');
      $(this).addClass('active').attr('aria-selected', 'true');
      container.find('.accordion[data-title="' + $(this).id + '"]').addClass('active');
      getEvents(params, handleMultipleEvents, false, container);
    }).keyup(function onKeyUp(e) {
      return keyHandler(e);
    });

    var toggleSearchTools = container.find($('.events-searchtools-toggle'));
    if (toggleSearchTools[0]) {
      toggleSearchTools.click(function onToggleSeachTools() {
        if (container.find($('.events-searchtools-toggle')).hasClass('open')) {
          container.find($('.events-searchtools-container')).hide();
          container.find($('.events-searchtools-toggle')).removeClass('open');
        } else {
          container.find($('.events-searchtools-container')).show();
          container.find($('.events-searchtools-toggle')).addClass('open');      
        }
      });
    }
    var datepickerLang = container.find('.event-date-container').data('lang');
    $('.event-datepicker').datepicker({
      'language': datepickerLang,
      'format': 'dd.mm.yyyy',
      'weekStart': 1,
      'autoclose': true
    });

    if (container.find($('.events-searchtools-container'))[0]) {
      container.find($('.linked-event-search')).click(function onSearchClick() {
        var activeParams = container.find($('.event-tab.active')).data('params');
        var startDate = container.find($('.event-date-start'))[0].value
          ? {'start': container.find($('.event-date-start'))[0].value.replace(/\./g, '-')}
          : '';
        var endDate = container.find($('.event-date-end'))[0].value
          ? {'end': container.find($('.event-date-end'))[0].value.replace(/\./g, '-')}
          : '';
        var textSearch = container.find($('.event-text-search'))[0].value
          ? {'text': container.find($('.event-text-search'))[0].value}
          : '';

        var newParams = {};
        newParams.query = $.extend(newParams.query, activeParams, startDate, endDate, textSearch);
        getEvents(newParams, handleMultipleEvents, false, container);
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
        getEvents(tabParams, handleMultipleEvents, false, container);
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

  var my = {
    initEventsTabs: initEventsTabs,
    getEventContent: getEventContent,
  };

  return my;
})();
