// Registration form license interval toggle (annual vs monthly)
(function($) {
  let month = $('.month');
  let year = $('.year');
  $('.interval').each(function() {
    let _this = $(this);
    _this.on('click', function() {
      if(_this.hasClass('yearly')) {
        let _that = $('.monthly');
        month.hide();
        year.show();
        toggleStyles(_this, _that);
      } else {
        let _that = $('.yearly');
        month.show();
        year.hide();
        toggleStyles(_this, _that);
      }
    });
  });

  function toggleStyles(_this, _that){
    _this
      .addClass('interval-focused')
      .removeClass('interval-blurred');
    _this
      .find('.text')
      .addClass('text-focused')
      .removeClass('text-blurred');
    _that
      .addClass('interval-blurred')
      .removeClass('interval-focused');
    _that
      .find('.text')
      .addClass('text-blurred')
      .removeClass('text-focused');
  }

})(jQuery);
