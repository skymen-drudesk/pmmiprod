(function (window, Selectize) {
  'use strict';
  if (Selectize) {
    Selectize.define('prevent_items_backspace_delete', function () {
      var self = this;
      this.onKeyDown = (function () {
        var original = self.onKeyDown;
        return function (e) {
          if (e.keyCode === 8) {
            return function () {};
          }
          return original.apply(this, arguments);
        };
      })();
    });
  }
})(window, window.Selectize);
