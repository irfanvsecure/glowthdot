/* Widgets inside the page (calculators, tabs, counters) */
(function(){
  var el=document.getElementById('ggmm');
  if(!el||el.dataset.on)return;
  el.dataset.on=1;

  var rows=el.querySelectorAll('.row');

  function cnt(n,to){
    var t=Date.now();
    (function f(){
      var p=Math.min(1,(Date.now()-t)/1500);
      n.textContent=Math.round(
        to*(1-Math.pow(1-p,3))
      ).toLocaleString();

      if(p<1)requestAnimationFrame(f)
    })()
  }

  function play(){
    rows.forEach(function(x){
      x.classList.remove('on')
    });

    rows.forEach(function(x,i){
      setTimeout(function(){
        x.classList.add('on')
      },350+i*400)
    });

    el.querySelectorAll('[data-c]').forEach(function(n){
      cnt(n,+n.dataset.c)
    });

    setTimeout(play,8000)
  }

  play();
})();;
