/* Widgets inside the page (calculators, tabs, counters) */
(function(){
  var el=document.getElementById('ggck');
  if(!el||el.dataset.on)return;
  el.dataset.on=1;

  var it=el.querySelectorAll('.it');

  function play(){
    it.forEach(function(x){
      x.classList.remove('on')
    });

    it.forEach(function(x,i){
      setTimeout(function(){
        x.classList.add('on')
      },400+i*450)
    });

    setTimeout(play,6500)
  }

  play();
})();;
