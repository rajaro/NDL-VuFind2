/*global finna */
finna.organisationList = (function finnaOrganisationList() {
  function initOrganisationPageLinksForParticipants() {
  var ids = [];
  var timeout = false;
    $('.page-link').not('.done').map(function setupOrganisationPageLinks() {
      $(this).one('inview', function onInViewLink() {
        ids.push($(this).data('organisation'));
        getOrganisationLinks();
      });
    });

    function getOrganisationLinks() {
      if (!timeout) {
        timeout = true;
        setTimeout(function getLinks() {
          finna.layout.getOrganisationPageLink(ids, false, false, function onGetOrganisationPageLink(response) {
            if (response) {
              $.each(response, function handleLink(id, success) {
                if (success) {
                  $.each(success.items, function handleLinks(id, url) {
                  var link = $('.organisations .page-link[data-organisation="' + id + '"]');
                  link.wrap($('<a/>').attr('href', url));
                  });
                }
              });
            }
          });
          ids = [];
          timeout = false;
        }, 500);
      }
    }
  }
  var my = {
    init: function init() {
      initOrganisationPageLinksForParticipants();
    }
  };

  return my;
})();
