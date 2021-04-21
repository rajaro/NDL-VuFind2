/* global finna */
finna.advSearch = (function advSearch() {

  function initForm() {
    var form = $('.template-dir-search #advSearchForm');
    var container = form.find('.ranges-container .slider-container').closest('fieldset');
    var field = container.find('input[name="daterange[]"]').eq(0).val();
    var fromField = container.find('#' + field + 'from');
    var toField = container.find('#' + field + 'to');
    form.on('submit', function formSubmit(event) {
      if (typeof form[0].checkValidity == 'function') {
        // This is for Safari, which doesn't validate forms on submit
        if (!form[0].checkValidity()) {
          event.preventDefault();
          return;
        }
      } else {
        // JS validation for browsers that don't support form validation
        fromField.removeClass('invalid');
        toField.removeClass('invalid');
        if (fromField.val() && toField.val() && parseInt(fromField.val(), 10) > parseInt(toField.val(), 10)) {
          fromField.addClass('invalid');
          toField.addClass('invalid');
          event.preventDefault();
          return;
        }
      }
      // Convert date range from/to fields into a "[from TO to]" query
      container.find('input[type="hidden"]').attr('disabled', 'disabled');
      var from = fromField.val() || '*';
      var to = toField.val() || '*';
      if (from !== '*' || to !== '*') {
        var filter = field + ':"[' + from + " TO " + to + ']"';

        $('<input>')
          .attr('type', 'hidden')
          .attr('name', 'filter[]')
          .attr('value', filter)
          .appendTo($(this));
      }
    });

    fromField.change(function fromFieldChange() {
      toField.attr('min', fromField.val());
    });
    toField.change(function toFieldChange() {
      fromField.attr('max', toField.val());
    });
    $('.adv-search-menu-toggle').on('click', function toggleActive() {
      $('.adv-search-menu').toggleClass("active");
    });
  }

  /**
   * Initialize advanced search map
   *
   * @param options Array of options:
   *   tileLayer     L.tileLayer Tile layer
   *   center        L.LatLng    Map center point
   *   zoom          int         Initial zoom level
   *   items         array       Items to draw on the map
   */
  function initMap(_options) {
    var mapCanvas = $('.selection-map-canvas');
    var mapData = finna.map.initMap(mapCanvas, true, _options);
    var drawnItems = mapData.drawnItems;

    mapCanvas.closest('form').on('submit', function mapFormSubmit() {
      var filters = '';
      drawnItems.eachLayer(function mapLayerToSearchFilter(layer) {
        var latlng = layer.getLatLng();
        var value = '{!geofilt sfield=location_geo pt=' + latlng.lat + ',' + latlng.lng + ' d=' + (layer.getRadius() / 1000) + '}';
        if (filters) {
          filters += ' OR ';
        }
        filters += value;
      });
      if (filters) {
        var field = $('<input type="hidden" name="filter[]"/>').val(filters);
        mapCanvas.closest('form').append(field);
      }
    });
  }

  var my = {
    init: function init() {
      initForm();
    },
    initMap: initMap
  };

  return my;

})();
