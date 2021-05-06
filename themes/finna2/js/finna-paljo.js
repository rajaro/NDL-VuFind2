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
        });
  }

  function initPaljoMyresearch() {
    initVolumeCode();
    initChangePaljoId();
  }

  function initVolumeCode() {
    $('.volume-code-toggle').click(function onVolumeCodeToggleClick() {
      $('.paljo-volume-code').toggleClass('hidden');
    });
    // delete volume vode
    $('.paljo-volume-codes').find('i.remove').off('click').on('click', function onClickRemove(e) {
      var target = $(this);
      var form = $('.delete-volume-code');
      var prompt = form.find('.dropdown-menu');

      function repositionPrompt(ev, data) {
        var pos = target.offset();
        var left = data.w / 2 - prompt.width() / 2;

        prompt.css({
          'left': left,
          'top': pos.top + 30
        });
      }

      function initRepositionListener() {
        $(window).on('throttled-resize.finna', repositionPrompt);
      }

      prompt.find('.confirm').off('click').on('click', function onClickConfirm(ev) {
        form.submit();
        ev.preventDefault();
      });
      prompt.find('.cancel').off('click').on('click', function onClickCancel(ev) {
        $(window).off('throttled-resize.finna', repositionPrompt);
        prompt.hide();
        ev.preventDefault();
      });

      repositionPrompt({}, {w: $(window).width(), h: $(window).height()});
      initRepositionListener();
      prompt.show();
      prompt.find('.confirm a').focus();
      e.preventDefault();
    });
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

  function initChangePaljoId() {
    var toggleBtn = $('.change-paljo-id-btn');
    var changeForm = $('#change-paljo-id-form');
    toggleBtn.click(function onToggleClick() {
      changeForm.toggleClass('hidden');
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
    var licenseDesc = priceType[0].dataset.licenseDesc;
    if (license) {
      $('span.paljo-image-license').html(license);
    }
    if (licenseDesc) {
      $('span.paljo-image-license').append('<br>' + licenseDesc);

    }
    $('select.paljo-price-type-menu').change(function onPriceTypeChange() {
      price = $(this).find('.paljo-price-type:selected').data('price');
      $('span.paljo-price').html(price);
      license = $(this).find('.paljo-price-type:selected').data('license');
      $('span.paljo-image-license').html(license);
      licenseDesc = $(this).find('.paljo-price-type:selected').data('licenseDesc');
      $('span.paljo-image-license').append('<br>' + licenseDesc);
    });

    // test
    $('button.discount').click(function ondiscountclick(e) {
      e.preventDefault();
      getPaljoDiscount();
    });
    // test
  }

  var my = {
    initPaljoPrice: initPaljoPrice,
    initVolumeCode: initVolumeCode,
    initPaljoTabs: initPaljoTabs,
    initPaljoMyresearch: initPaljoMyresearch,
    checkPaljoAvailability: checkPaljoAvailability
  };

  return my;
})();