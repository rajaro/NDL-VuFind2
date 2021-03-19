/*global VuFind, finna */
finna.paljo = (function finnaPaljo() {
    function getPaljoDiscount() {
      var email = $('.paljo-user-email')[0].innerHTML;
      var code = $('input.paljo-volume-code')[0].value;
      var orgId = $('input[name="organisationId"]')[0].value;
      var imageId = $('input[name="image-id"]')[0].value;
      var priceType = $('.paljo-price-type:selected')[0].value;
      $.ajax({
        type: 'GET',
        dataType: 'json',
        url: VuFind.path + '/AJAX/JSON?method=getPaljoDiscount',
        data: {
          'email': email,
          'code': code,
          'orgId': orgId,
          'imageId': imageId,
          'priceType': priceType
        }
      })
        .done(function onGetDiscount(response) {
          if (typeof response.data.price !== undefined)
          $('span.paljo-price').html(response.data.price);
          // var discount = response.data.discount;
          // var currentPrice = $('span.paljo-price').html();
          // var newPrice = (1 - discount / 100) * currentPrice
          // newPrice = newPrice.toFixed(2);
          // $('span.paljo-price').html(newPrice);
        });
  }

  function initVolumeCode() {
    var volumeCode = $('.save-volume-code')[0];
    $('.save-volume-code-btn').click(function onSaveVolumeCode() {
      if (volumeCode.value) {
        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: VuFind.path + '/AJAX/JSON?method=savePaljoVolumeCode',
          data: {'volumeCode': volumeCode.value}
        })
        .done(function onSaveCode(response) {
          if (typeof response.data.discount === undefined) {
            
          }
        });
      }
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
      dataType: 'json',
      url: VuFind.path + '/AJAX/JSON?method=getPaljoAvailability',
      data: {
        'imageId': imageId,
        'organisationId': organisationId
      }
    })
    .done(function onGetAvailability(response) {
      if (response.data) {
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
    var price = priceType[0].dataset.price
    if (price) {
      $('span.paljo-price').html(price);
    }
    var license = priceType[0].dataset.license;
    if (license) {
      $('span.paljo-image-license').html(license);
    }
    $('select.paljo-price-type-menu').change(function onPriceTypeChange() {
      price = $(this).find('.paljo-price-type:selected').data('price');
      $('span.paljo-price').html(price);
      license = $(this).find('.paljo-price-type:selected').data('license');
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
    initVolumeCode: initVolumeCode,
    checkPaljoAvailability: checkPaljoAvailability
  };

  return my;
})();