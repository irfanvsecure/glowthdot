/* Desktop or mobile header, by screen width: the header styles key off these two body classes */
(function () {
  var mq = window.matchMedia('(max-width:921.99px)');
  function apply(isMobile) {
    var b = document.body.classList;
    b.toggle('h-header-break-point', isMobile);
    b.toggle('h-desktop', !isMobile);
  }
  apply(mq.matches);
  if (mq.addEventListener) {
    mq.addEventListener('change', function (e) { apply(e.matches); });
  } else if (mq.addListener) {
    mq.addListener(function (e) { apply(e.matches); });
  }
})();
