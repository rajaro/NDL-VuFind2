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
        .done(function onGetDiscount(response) {
          var discount = response.data.discount;
          var currentPrice = $('span.paljo-price').html();
          var newPrice = (1 - discount / 100) * currentPrice
          newPrice = newPrice.toFixed(2);
          $('span.paljo-price').html(newPrice);
        });
  }
  function checkPaljoAvailability() {
    if (typeof $('.paljo-link') === 'undefined') {
      return;
    }
    var imageId = $('.paljo-link').data('collecteid');
    var recordId = $('.record-main .hiddenId')[0]
      ? $('.record-main .hiddenId')[0].value
      : '';
    var organisationId = $('.record-organisation-info .organisation-page-link').data('organisation');
    $.ajax({
      type: 'GET',
      dataType: 'json',
      url: VuFind.path + '/AJAX/JSON?method=getPaljoAvailability',
      data: {
        'imageId': imageId,
        'organisationId': organisationId
      }
    })
    .done(function onGetAvailability(response) {
      if (response) {
        $('.paljo-link').removeClass('hidden');
        var url = VuFind.path + '/Paljo/Subscription'
          + '?imageId=' + imageId
          + '&recordId=' + recordId
          + '&organisationId=' + organisationId;
        $('.paljo-link').attr('href', url);
      }
    });
  }

  function initPaljoPrice() {
    var priceType = $('select.paljo-price-type-menu').find(':selected');
    var price = priceType[0].value
    if (price) {
      $('span.paljo-price').html(price);
    }
    var license = priceType[0].dataset.license;
    if (license) {
      $('span.paljo-image-license').html(license);
    }
    $('select.paljo-price-type-menu').change(function onPriceTypeChange() {
      price = $(this)[0].value;
      $('span.paljo-price').html(price);

      license = $(this)[0];
      console.log(license)
      $('span.paljo-image-license').html(license)
    });

    // test
    $('button.discount').click(function ondiscountclick() {
      getPaljoDiscount();
    });
    // test
  }

  var my = {
    initPaljoPrice: initPaljoPrice,
    checkPaljoAvailability: checkPaljoAvailability
  };

  return my;
})();