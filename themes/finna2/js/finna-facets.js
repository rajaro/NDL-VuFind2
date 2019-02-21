/* global VuFind */
VuFind.register('sideFacets', function SideFacets() {
  function facetSessionStorage(e) {
    var source = $('#result0 .hiddenSource').val();
    var id = e.target.id;
    var key = 'sidefacet-' + source + id;
    if (!sessionStorage.getItem(key)) {
      sessionStorage.setItem(key, document.getElementById(id).className);
    } else {
      sessionStorage.removeItem(key);
    }
  }

  function init() {
    // Side facet status saving
    $('.facet-group .collapse').each(function openStoredFacets(index, item) {
      var source = $('#result0 .hiddenSource').val();
      var storedItem = sessionStorage.getItem('sidefacet-' + source + item.id);
      if (storedItem) {
        var saveTransition = $.support.transition;
        try {
          $.support.transition = false;
          if ((' ' + storedItem + ' ').indexOf(' in ') > -1) {
            $(item).collapse('show');
          } else {
            $(item).collapse('hide');
          }
        } finally {
          $.support.transition = saveTransition;
        }
      }
    });
    $('.facet-group').on('shown.bs.collapse', facetSessionStorage);
    $('.facet-group').on('hidden.bs.collapse', facetSessionStorage);
  }
  return {
    init: init
  };
});
