function responsiveInit(){
  var gotSmall = false;

  function setHeaderStyle(){
    if (window.scrollY < 130)
      gotSmall = false;
    if (window.scrollY >= 280)
      gotSmall = true;
    var y = gotSmall ? 280 : window.scrollY;


    // resizing is done when scrollY=280
    var height = Math.max(40, 110 - y / 4);
    $('header>div').css('height', height + "px");

    var listMargin = Math.max(height / 2 - 20, 0);
    $('header ul').css('top', listMargin + "px");

    var opacity = Math.max(.765625, 1 - (y - 130) / 640);
    $('header').css('opacity', opacity);

  }
  $(window).on("scroll", setHeaderStyle);
}

$(responsiveInit);

