/*global VuFind, finna */
finna.paljo = (function finnaPaljo() {
    function getPaljoDiscount() {
      var email = $('.paljo-user-email')[0].innerHTML;
      var code = $('input.paljo-volume-code')[0].value;
      $.ajax({
        type: 'GET',
        dataType: 'json',
        url: VuFind.path + '/AJAX/JSON?method=getPaljoDiscount',
        data: {'email': email, 'code': code}
      })
        .done(function onGetDiscount() {
        });
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

    // test
    $('button.discount').click(function ondiscountclick() {
      getPaljoDiscount();
    });
    // test
  }

  var my = {
    initPaljoPrice: initPaljoPrice
  };

  return my;
})();