(function ($) {
  Drupal.behaviors.pmmiVideoGallerySlider = {
    attach: function () {
      // Connect slick slider to each
      // class="video-gallery--slider" DOM element.
      $('.video-gallery--slider').each(function () {
        // Navigation container.
        const $nav = $(this).find('.video-gallery__slider-navigation');
        const $slickSlider = $(this).find('.video-gallery__slider > div');
        const $countBlock = $nav.find('.video-gallery__slider-counter');

        // Add text in counter container in format: "1 of 6".
        $slickSlider.on('init reInit afterChange', function (event, slick, currentSlide) {
          const itemNumber = (currentSlide ? currentSlide : 0) + 1;
          $countBlock.text(itemNumber + ' ' + Drupal.t('of') + ' ' + slick.slideCount);
        });

        // Initiate slick slider.
        $slickSlider.once().slick({
          dots: true,
          slide: '.video-gallery__slider-item',
          infinite: false,
          slidesToShow: 3,
          slidesToScroll: 1,
          lazyLoad: 'ondemand',
          nextArrow: '<span class="video-gallery__slider-arrow video-gallery__slider-arrow--right"></span>',
          prevArrow: '<span class="video-gallery__slider-arrow video-gallery__slider-arrow--left"></span>',
          appendArrows: $nav,
          appendDots: $nav,
          centerPadding: '100px',
          responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              },
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                dots: false,
              },
            },
          ],
        });
      });
    },
  };

  Drupal.behaviors.pmmiVideoGalleryExpanded = {
    attach: function() {
      $('.video-gallery--expanded').each(function () {
        const $_that = $(this);
        // Click event for video title.
        $(this).find('.video-gallery__expanded-items-title').off().on('click touchstart', () => {
          // Close container if current container is active.
          if ($_that.hasClass('active')) {
            $_that.toggleClass('active');
            $('.video-gallery--expanded').removeClass('active');
          }
          else {
            // Collapse other containers and make active clicked container.
            $('.video-gallery--expanded').removeClass('active');
            $_that.toggleClass('active');
          }
        });
      });
    },
  };
}(jQuery, window, Drupal));
