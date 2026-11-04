(function(){
  var d=document, root=d.documentElement; root.classList.add('js');
  function $(s,c){return (c||d).querySelector(s)}
  function $$(s,c){return Array.prototype.slice.call((c||d).querySelectorAll(s))}

  /* Sticky header shadow + back to top */
  var header=$('.header'), top=$('.to-top');
  function onScroll(){
    var y=window.scrollY||0;
    if(header) header.classList.toggle('scrolled',y>10);
    if(top) top.classList.toggle('show',y>600);
  }
  window.addEventListener('scroll',onScroll,{passive:true}); onScroll();
  if(top) top.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})});

  /* Mobile nav */
  var nav=$('.nav'), scrim=$('.scrim'), btn=$('.menu-btn'), close=$('.nav-close');
  function toggle(o){
    nav.classList.toggle('open',o); scrim.classList.toggle('open',o);
    btn.setAttribute('aria-expanded',o); d.body.style.overflow=o?'hidden':'';
  }
  if(btn){ btn.addEventListener('click',function(){toggle(true)});
    close.addEventListener('click',function(){toggle(false)});
    scrim.addEventListener('click',function(){toggle(false)});
    $$('.nav a').forEach(function(a){a.addEventListener('click',function(){toggle(false)})});
    d.addEventListener('keydown',function(e){if(e.key==='Escape')toggle(false)}); }

  /* Hero image rollover slider */
  var hero=$('.hero');
  if(hero){
    var slides=$$('.slide',hero), dots=$$('.dots button',hero), i=0, timer, DUR=6500;
    hero.style.setProperty('--dur',DUR+'ms');
    function go(n){
      i=(n+slides.length)%slides.length;
      slides.forEach(function(s,k){s.classList.toggle('is-active',k===i);s.setAttribute('aria-hidden',k!==i)});
      dots.forEach(function(b,k){b.classList.remove('on'); void b.offsetWidth; if(k===i)b.classList.add('on'); b.setAttribute('aria-current',k===i)});
    }
    function play(){clearInterval(timer); timer=setInterval(function(){go(i+1)},DUR)}
    function stop(){clearInterval(timer)}
    $('.next',hero).addEventListener('click',function(){go(i+1);play()});
    $('.prev',hero).addEventListener('click',function(){go(i-1);play()});
    dots.forEach(function(b,k){b.addEventListener('click',function(){go(k);play()})});
    var tx=0;
    hero.addEventListener('touchstart',function(e){tx=e.touches[0].clientX},{passive:true});
    hero.addEventListener('touchend',function(e){var dx=e.changedTouches[0].clientX-tx; if(Math.abs(dx)>50){go(i+(dx<0?1:-1));play()}});
    d.addEventListener('visibilitychange',function(){d.hidden?stop():play()});
    go(0); play();
  }

  /* Reveal on scroll */
  var els=$$('.reveal');
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.12,rootMargin:'0px 0px -40px 0px'});
    els.forEach(function(el){io.observe(el)});
  } else els.forEach(function(el){el.classList.add('in')});

  /* Year */
  $$('[data-year]').forEach(function(e){e.textContent=new Date().getFullYear()});

  /* Contact form -> send-mail.php */
  var form=$('#contact-form');
  if(form){
    var note=$('.form-note',form), submit=$('button[type=submit]',form);
    function setBad(f,bad){f.parentNode.classList.toggle('bad',bad)}
    form.addEventListener('submit',function(e){
      e.preventDefault(); note.className='form-note'; note.textContent='';
      var ok=true, name=form.name, email=form.email, msg=form.message;
      var re=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      setBad(name,name.value.trim().length<2); setBad(email,!re.test(email.value.trim())); setBad(msg,msg.value.trim().length<10);
      if($$('.field.bad',form).length) return;
      var label=submit.innerHTML; submit.disabled=true; submit.firstChild.textContent='Sending… ';
      fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'}})
        .then(function(r){return r.json().then(function(j){return {ok:r.ok&&j.ok,j:j}})})
        .then(function(res){
          if(res.ok){ note.className='form-note ok'; note.textContent='Thank you! Your message has been sent. We will get back to you shortly.'; form.reset(); }
          else throw new Error(res.j&&res.j.error||'fail');
        })
        .catch(function(){
          note.className='form-note fail';
          note.innerHTML='We could not send your message right now. Please email us directly at <a href="mailto:info@empretechnologies.com.ng">info@empretechnologies.com.ng</a> or call +234 810 370 2504.';
        })
        .then(function(){submit.disabled=false; submit.innerHTML=label});
    });
  }

  /* Course enrolment -> enrol.php */
  var ef=$('#enrol-form');
  if(ef){
    var enote=$('.form-note',ef), esub=$('button[type=submit]',ef);
    ef.addEventListener('submit',function(e){
      e.preventDefault(); enote.className='form-note'; enote.textContent='';
      var f=ef.elements, re=/^[^\s@]+@[^\s@]+\.[^\s@]+$/, age=parseInt(f.age.value,10);
      function bad(el,b){el.parentNode.classList.toggle('bad',b)}
      bad(f.name,f.name.value.trim().length<3); bad(f.age,!(age>=10&&age<=99)); bad(f.sex,!f.sex.value);
      bad(f.location,f.location.value.trim().length<2); bad(f.email,!re.test(f.email.value.trim()));
      bad(f.phone,!/^\+?[0-9 ()\-]{7,20}$/.test(f.phone.value.trim()));
      if($$('.field.bad',ef).length) return;
      var label=esub.innerHTML; esub.disabled=true; esub.firstChild.textContent='Submitting… ';
      fetch(ef.action,{method:'POST',body:new FormData(ef),headers:{'Accept':'application/json'}})
        .then(function(r){return r.json().then(function(j){return {ok:r.ok&&j.ok,j:j}})})
        .then(function(res){
          if(!res.ok){ enote.className='form-note fail'; enote.textContent=res.j.error||'Something went wrong. Please try again.'; return; }
          $('#sid-val').textContent=res.j.student_id; $('#sum-name').textContent=res.j.name; $('#sum-course').textContent=res.j.course;
          $('#enrol-form-wrap').hidden=true; $('#enrol-done').hidden=false;
          $('#enrol').scrollIntoView({behavior:'smooth',block:'start'});
        })
        .catch(function(){
          enote.className='form-note fail';
          enote.innerHTML='We could not complete your enrolment right now. Please email <a href="mailto:info@empretechnologies.com.ng">info@empretechnologies.com.ng</a> or call +234 810 370 2504.';
        })
        .then(function(){esub.disabled=false; esub.innerHTML=label});
    });
  }
})();
