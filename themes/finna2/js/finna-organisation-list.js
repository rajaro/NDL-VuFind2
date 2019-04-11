/*global finna */
var ids = [];
finna.organisationList = (function finnaOrganisationList() {
  function initOrganisationPageLinksForParticipants() {
  /*  $('.organisations .page-link').not('.done').map(function setupOrganisationPageLinks() {
      var asd = $.makeArray($(this).one('inview', function onInViewLink() {
        return $(this).data('organisation');
      }));
    });
*/
    var ids = $.makeArray($('.organisations .page-link').not('.done').map(function getId() {
      $(this).one('inview', function onInViewLink() {
        return $(this).data('organisation');
      });
     // return $(this).data('organisation');
    }));
    if (!ids.length) {
      return;
    }
    finna.layout.getOrganisationPageLink(ids, false, false, function onGetOrganisationPageLink(response) {
      if (response) {
        $.each(response, function handleLink(index, item) {
          if (item) {
            $.each(item.items, function handleLink(id, url) {
              var link = $('.organisations .page-link[data-organisation="' + id + '"]');
              link.wrapInner('<a href="http://localhost:8080/vufind2/OrganisationInfo/Home?id=' + url + '"></a>');
            //link.wrap($('<a/>').attr('href', url));
            });
          }
        });
      }
    });
  }

 /* $('.organisations .page-link').not('.done').map(function setupOrganisationPageLinks() {
      $(this).one('inview', function onInViewLink() {
        finna.layout.getOrganisationPageLink($(this).data('organisation'), false, false, function onGetOrganisationPageLink(response) {
          if (response) {
            $.each(response, function handleLink(id, key) {
              var link = $('.organisations .page-link[data-organisation="' + id + '"]');
              link.wrapInner('<a href="http://localhost:8080/vufind2/OrganisationInfo/Home?id=' + key + '"></a>');
            });
          }
        });
      });
    });
  }*/

  var my = {
    init: function init() {
      initOrganisationPageLinksForParticipants();
    }
  };

  return my;
})();
