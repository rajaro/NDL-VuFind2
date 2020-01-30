/*global VuFind, finna */
finna.linkedEvents = (function finnaLinkedEvents() {
  getEvents();
  function getEvents() {
    console.log('aaaaaaa');
    var url = 'https://satakuntaevents.fi/api/v2/event/?publisher=pori:kaupunki';
    var url = 'http://localhost:8080/vufind2' + '/AJAX/JSON?method=getLinkedEvents';
    $.ajax({
      type: 'POST',
      url: url,
      dataType: 'json',
      data: {test: null}
    })
      .done(function onGetEventsDone(response) {
        console.log(response);
        $('.events').html(response.data);
        ///$('.events').html('WHAT');
      })
      .fail(function onGetEventsFail() {
       
      });

    return false;
  }

  var my = {
    getEvents: getEvents
  };

  return my;
})();
