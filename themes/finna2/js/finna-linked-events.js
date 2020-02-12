/*global VuFind, finna */
finna.linkedEvents = (function finnaLinkedEvents() {
  getEvents('?keyword=pori:topic:music');
  function getEvents(params) {
    var url = 'http://localhost:8080/vufind2' + '/AJAX/JSON?method=getLinkedEvents';
    // $.getJSON(url)
    //   .done(function onGetEventsDone(response) {
    //     if (response.data) {
    //       $('.events').html(response.data);
    //     }
    //   });
    $.ajax({
      url: url,
      dataType: 'json',
      data: {'params': params}
    })
      .done(function onGetEventsDone(response) {
        if (response.data) {
          return response.data;
          $('.events').html(response.data);
        }
      })
      .fail(function onGetEventsFail() {
        $('.events').html('Events could not be loaded');
      });

    return false;
  }

  function initEventsTabs(container) {
    var container = $('.events-tabs');
    container.find($('.nav-item')).click(function eventTabClick() {
      var params = $(this).data('params');
      var content = getEvents(params);
      $('.linked-events-content').html(content);

    });

  }

  var my = {
    getEvents: getEvents,
    initEventsTabs: initEventsTabs
  };

  return my;
})();
