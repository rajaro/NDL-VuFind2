/*global VuFind, finna */
finna.linkedEvents = (function finnaLinkedEvents() {
  function getEvents(params, container) {
    var url = 'http://localhost:8080/vufind2' + '/AJAX/JSON?method=getLinkedEvents';
    $.ajax({
      url: url,
      dataType: 'json',
      data: {'params': params}
    })
      .done(function onGetEventsDone(response) {
        if (response.data) {
          container.html(response.data);
        }
      })
      .fail(function onGetEventsFail() {
        container.html('Events could not be loaded');
      });

    return false;
  }

  function initEventsTabs(container) {
    var container = $('.events-tabs');
    container.find($('li.nav-item.event-tab')).click(function eventTabClick() {
      var params = $(this).data('params');
      $('li.nav-item.event-tab').removeClass('active');
      $(this).addClass('active');
      var eventContainer = $('.linked-events-content');
      getEvents(params, eventContainer);
    });
  }

  var my = {
    getEvents: getEvents,
    initEventsTabs: initEventsTabs
  };

  return my;
})();
