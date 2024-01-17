/* global VuFind, finna, Splide */
finna.carouselManager = (() => {
  const breakpointSettingMappings = {
    desktop: 'perPage',
    'desktop-small': 992,
    tablet: 768,
    mobile: 480
  };

  /**
   * Calculate the gap value from amount of items per page
   * 
   * @param {int} perPage The value to calculate gap value from
   * @returns {int} Gap value 
   */
  function calculateGapValue(perPage) {
    if (perPage % 2 === 0 || perPage === 1) {
      return 10;
    } else {
      return 9;
    }
  }

  /**
   * Settings in finna to settings in splide.
   * Key is the setting in the ini file of an rss feed.
   * Value is either a value or a function returning the value.
   */
  const settingNameMappings = {
    autoplay: (value) => {
      let valueToInt = parseInt(value);
      if (!isNaN(valueToInt) && valueToInt > 0) {
        return {
          autoplay: true,
          interval: valueToInt
        };
      }
      return {autoplay: false};
    },
    height: (value) => { return {height: parseInt(value)}; },
    width: (value) => { return {fixedWidth: parseInt(value)}; },
    omitEnd: 'omitEnd',
    pagination: 'pagination',
    gap: 'gap',
    focus: 'focus',
    slidesToShow: (itemsPerPage) => {
      const breakpoints = {};
      let perPage = 0;
      let gap;
      for (const [key, value] of Object.entries(itemsPerPage)) {
        const bp = breakpointSettingMappings[key] || '';
        switch (bp) {
        case 'perPage':
          perPage = value;
          gap = calculateGapValue(value);
          break;
        case '':
          break;
        default:
          gap = calculateGapValue(value);
          breakpoints[bp] = {
            perPage: value,
            gap,
          };
          break;
        }
      }
      return {
        breakpoints,
        perPage,
        gap,
      };
    },
    type: (value) => {
      let direction = 'ltr';
      let classes = {
        prev: 'splide__arrow--prev carousel-arrow ',
        next: 'splide__arrow--next carousel-arrow ',
        arrows: 'splide__arrows carousel-arrows '
      };
      switch (value) {
      case 'carousel-vertical':
        classes.prev += 'up';
        classes.next += 'down';
        classes.arrows += 'vertical';
        direction = 'ttb';
        break;
      case 'carousel':
        direction = 'ltr';
        classes.prev += 'left';
        classes.next += 'right';
        classes.arrows += 'horizontal';
        break;
      }
      return {
        direction,
        classes
      };
    },
    slidesToScroll: 'perMove',
    scrollSpeed: 'speed',
    i18n: (translations) => {
      return {
        i18n: {
          prev: VuFind.translate(translations.prev || 'Carousel::Prev'),
          next: VuFind.translate(translations.next || 'Carousel::Next'),
          first: VuFind.translate(translations.first || 'Carousel::First'),
          last: VuFind.translate(translations.last || 'Carousel::Last'),
          slideX: VuFind.translate(translations.slide || 'Carousel::go_to_page'),
          pageX: VuFind.translate(translations.page || 'Carousel::page_number'),
          play: VuFind.translate(translations.play || 'Carousel::Start Autoplay'),
          pause: VuFind.translate(translations.pause || 'Carousel::Stop Autoplay'),
          select: VuFind.translate(translations.select || 'Carousel::Select Page'),
          slideLabel: VuFind.translate(translations.label || 'Carousel::slide_label'),
        }
      };
    }
  };

  /**
   * Merge sub objects into target from source
   *
   * @param {Object} target To merge key/values to
   * @param {Object} source To merge key/values from
   *
   * @returns {Object} Merged object
   */
  function deepMerge(target, source) {
    if (typeof target === 'object' && typeof source === 'object') {
      for (const key in source) {
        if (typeof source[key] === 'object') {
          if (!target[key]) {
            Object.assign(target, { [key]: {} });
          }
          deepMerge(target[key], source[key]);
        } else {
          Object.assign(target, { [key]: source[key] });
        }
      }
    }
    return target;
  }

  /**
   * Converts settings into compatible Splide settings
   *
   * @param {Object} settings
   */
  function toSplideSettings(settings) {
    let splidied = {
      direction: 'ltr',
      type: 'slide',
      rewind: true,
      live: false
    };
    for (const [key, value] of Object.entries(settings)) {
      if (typeof settingNameMappings[key] !== 'undefined') {
        const newKey = settingNameMappings[key];
        if (typeof newKey === 'function') {
          const functionResult = newKey(value);
          splidied = deepMerge(splidied, functionResult);
        } else {
          splidied[newKey] = value;
        }
      }
    }
    return splidied;
  }

  /**
   * Turn given element into a carousel
   *
   * @param {HTMLElement} element  Element to turn into a carousel
   * @param {Object}      settings Old Finna settings for carousels
   *
   * @return {Splide}
   */
  function createCarousel(element, settings) {
    if (typeof settings.i18n === 'undefined') {
      settings.i18n = {};
    }
    const splideSettings = toSplideSettings(settings);
    var splide = new Splide(element, splideSettings);
    splide.on('pagination:updated', function onPaginationUpdate(data) {
      data.items.forEach(function setTabIndex(item) {
        item.button.setAttribute('tabindex', 0);
      });
    });
    return splide.mount();
  }

  return {
    createCarousel
  };
})();
