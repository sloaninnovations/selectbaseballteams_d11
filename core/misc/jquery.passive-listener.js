/**
 * @file
 * Makes certain jQuery listeners passive to improve scrolling performance.
 *
 * @link https://github.com/jquery/jquery/issues/2871
 * @link https://stackoverflow.com/a/62177358
 */

(($) => {
  $.event.special.touchstart = {
    setup: (_, ns, handle) => {
      this.addEventListener('touchstart', handle, {
        passive: !ns.includes('noPreventDefault'),
      });
    },
  };
  $.event.special.touchmove = {
    setup: (_, ns, handle) => {
      this.addEventListener('touchmove', handle, {
        passive: !ns.includes('noPreventDefault'),
      });
    },
  };
  $.event.special.wheel = {
    setup: (_, ns, handle) => {
      this.addEventListener('wheel', handle, { passive: true });
    },
  };
  $.event.special.mousewheel = {
    setup: (_, ns, handle) => {
      this.addEventListener('mousewheel', handle, { passive: true });
    },
  };
})(jQuery);
