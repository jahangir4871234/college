(function ($, Drupal, once) {
  Drupal.behaviors.headerMenu = {
    attach: function (context) {
      once('header-menu', '.menu-toggle', context).forEach(function (toggle) {
        var $toggle = $(toggle);
        var $menu = $('.menu-items').first();
        if (!$menu.length) {
          return;
        }
        if (!$menu.attr('id')) {
          $menu.attr('id', 'main-nav');
        }
        $toggle.attr('aria-controls', $menu.attr('id'));

        var setOpen = function (isOpen) {
          $toggle.toggleClass('is-open', isOpen);
          $menu.toggleClass('is-open', isOpen);
          $toggle.attr('aria-expanded', String(isOpen));
        };

        $toggle.on('click', function (e) {
          e.preventDefault();
          setOpen(!$menu.hasClass('is-open'));
        });

        $menu.on('click', 'a', function () {
          if (window.innerWidth < 992) {
            setOpen(false);
          }
        });

        $(document).on('keydown.headerMenu', function (e) {
          if (e.key === 'Escape' && $menu.hasClass('is-open')) {
            setOpen(false);
            $toggle.trigger('focus');
          }
        });

        $(document).on('click.headerMenu', function (e) {
          if ($menu.hasClass('is-open') && !$(e.target).closest('.header').length) {
            setOpen(false);
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);
