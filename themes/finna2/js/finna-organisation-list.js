/*global finna */
finna.organisationList = (function finnaOrganisationList() {
  function initOrganisationPageLinksForParticipants() {
    var ids = [];
    var timeout = false;
    $('.page-link').not('.done').map(function setupOrganisationPageLinks() {
      $(this).one('inview', function onInViewLink() {
        id = $(this).data('organisation');
        sector = $(this).data('sector');
        ids.push({'id': id, 'sector': sector});
        getOrganisationLinks();
      });
    });

    function getOrganisationLinks() {
      if (timeout) {
        clearTimeout(timeout);
      }
      timeout = setTimeout(function getLinks() {
        finna.layout.getOrganisationPageLink(ids, false, false, function onGetOrganisationPageLink(response) {
          if (response) {
            $.each(response, function handleLink(id, url) {
              var link = $('.organisations .page-link[data-organisation="' + id + '"]');
              link.wrapInner($('<a/>').attr('href', url));
            });
          }
        });
        ids = [];
        timeout = false;
      }, 500);
    }
  }
  var my = {
    init: function init() {
      initOrganisationPageLinksForParticipants();
    }
  };

  return my;
})();
