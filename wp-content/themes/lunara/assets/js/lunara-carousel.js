(()=>{const init=c=>{const s=[...c.querySelectorAll('.lunara-carousel-slide')];if(!s.length)return;const d=[...c.querySelectorAll('.lunara-carousel-dot')];let i=0;const show=n=>{i=(n+s.length)%s.length;s.forEach((el,k)=>el.classList.toggle('active',k===i));d.forEach((el,k)=>el.classList.toggle('active',k===i));};d.forEach((el,k)=>el.addEventListener('click',()=>show(k)));const ms=parseInt(c.dataset.autoplay||'5000',10);if(ms>0&&s.length>1){let t=setInterval(()=>show(i+1),ms);c.addEventListener('mouseenter',()=>{clearInterval(t);t=null;});c.addEventListener('mouseleave',()=>{if(!t)t=setInterval(()=>show(i+1),ms);});}};document.querySelectorAll('.lunara-carousel').forEach(init);})();

// Hide any "X / Y" fraction counter overlay inside the carousel (if a slider library injects it).
document.addEventListener('DOMContentLoaded',()=>{
  const roots=document.querySelectorAll('.lunara-carousel');
  if(!roots.length) return;
  roots.forEach(root=>{
    // Common class-based counters
    root.querySelectorAll('.swiper-pagination-fraction,.splide__pagination__counter,.slick-counter,.lunara-carousel-count,.lunara-slide-count,[data-slide-count]').forEach(el=>{
      el.style.display='none';
    });
    // Text-based "3 / 3" fallback
    root.querySelectorAll('*').forEach(el=>{
      if(el.children.length===0 && /^\d+\s*\/\s*\d+$/.test((el.textContent||'').trim())){
        el.style.display='none';
      }
    });
  });
});