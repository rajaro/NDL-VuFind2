/*global VuFind, finna */
finna.paljo = (function finnaPaljo() {
  function createPaljoAccount() {
      if ($('input.paljo-tos:checked') && $('input.paljo-email').text) {
        var email = $('input.paljo-email').text;
        var url = VuFind.path;
      }
  }
  function initPaljoPrice() {
    var price = $('option.paljo-price-type')[0].value
    if (price) {
      $('span.paljo-price').html(price);
    }
    $('select.paljo-price-type-menu').change(function onPriceTypeChange() {
      price = $(this)[0].value;
      $('span.paljo-price').html(price);

    });
  }

  var my = {
    createPaljoAccount: createPaljoAccount,
    initPaljoPrice: initPaljoPrice
  };

  return my;
})();