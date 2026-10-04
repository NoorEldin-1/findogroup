import './islands.jsx';
import Alpine from 'alpinejs';
window.Alpine=Alpine;
Alpine.start();
const toast=(message)=>{const el=document.getElementById('findo-toast');el.textContent=message;el.hidden=false;clearTimeout(window.findoToastTimer);window.findoToastTimer=setTimeout(()=>el.hidden=true,6000)};
window.addEventListener('findo:toast',e=>toast(e.detail));
window.addEventListener('findo:theme',()=>syncTheme());
function syncTheme(){const dark=document.documentElement.classList.contains('dark');document.querySelectorAll('video,img').forEach(el=>{for(const key of ['src','poster','srcset']){const value=el.getAttribute(key);if(value&&/hero-ai-(light|dark)/.test(value))el.setAttribute(key,value.replace(/hero-ai-(light|dark)/g,`hero-ai-${dark?'dark':'light'}`));}})}
syncTheme();
document.addEventListener('click',async e=>{
 const button=e.target.closest('[data-action],[data-share],[data-page]');if(!button)return;
 const action=button.dataset.action;
 if(action==='scroll-top'){window.scrollTo({top:0,behavior:'smooth'});}
 if(action==='theme'){document.documentElement.classList.toggle('dark');localStorage.setItem('findo-theme',document.documentElement.classList.contains('dark')?'dark':'light');syncTheme();}
 if(action==='language'){const next=document.documentElement.lang==='ar'?'en':'ar';document.cookie=`findo_language=${next};path=/;max-age=31536000;SameSite=Lax`;localStorage.setItem('findo-language',next);location.reload();}
 if(action==='mobile-menu'){const panel=document.querySelector('[data-mobile-panel]');if(panel){panel.classList.toggle('is-open');button.setAttribute('aria-expanded',String(panel.classList.contains('is-open')));}}
 if(button.dataset.page){const url=new URL(location.href);url.searchParams.set('page',button.dataset.page);location.href=url;}
 if(button.dataset.share){const platform=button.dataset.share;const url=encodeURIComponent(location.href);if(platform==='copy'){await navigator.clipboard.writeText(location.href);toast('تم نسخ الرابط');return;}const links={facebook:`https://www.facebook.com/sharer/sharer.php?u=${url}`,twitter:`https://twitter.com/intent/tweet?url=${url}&text=${encodeURIComponent(document.title)}`,linkedin:`https://www.linkedin.com/sharing/share-offsite/?url=${url}`};if(links[platform])window.open(links[platform],'_blank','noopener,noreferrer,width=600,height=450');}
});
document.querySelectorAll('details').forEach(el=>el.addEventListener('toggle',()=>el.dataset.state=el.open?'open':'closed'));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelector('[data-mobile-panel]')?.classList.remove('is-open')});
document.querySelectorAll('form').forEach((form,index)=>{
 if(form.closest('[data-island]'))return;
 form.addEventListener('submit',async e=>{
  e.preventDefault();if(!form.reportValidity())return;
  const fields={};form.querySelectorAll('input,textarea,select').forEach((el,i)=>{if(['submit','button','password','hidden','file'].includes(el.type))return;if(['checkbox','radio'].includes(el.type)&&!el.checked)return;fields[el.name||el.id||`field_${i}`]=el.value;});
  const find=(type,words)=>form.querySelector(`input[type=${type}]`)?.value||Object.entries(fields).find(([k])=>words.test(k))?.[1]||'';
  const name=find('text',/name|اسم/i),email=find('email',/email|mail|بريد/i),phone=find('tel',/phone|mobile|هاتف/i);
  if(form.querySelector('input[type=password]')){toast('تسجيل الدخول يحتاج ربط نظام الحسابات في المرحلة التالية.');return;}
  const submit=form.querySelector('[type=submit]');if(submit)submit.disabled=true;
  try {const response=await fetch('/forms/submit',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({form_key:form.id?.replace(/[^a-zA-Z0-9_-]/g,'')||`website-form-${index}`,source_path:location.pathname,name,email,phone,message:form.querySelector('textarea')?.value||'',fields})});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message);toast(data.message);form.reset();}catch(error){toast(error.message||'تعذر حفظ الطلب، حاول مرة أخرى.')}finally{if(submit)submit.disabled=false;}
 });
});
// Native scroll-snap replaces source carousel initialization without a page-wide React runtime.
document.querySelectorAll('[aria-roledescription=carousel]').forEach(carousel=>{const rail=carousel.querySelector('.overflow-hidden');if(!rail)return;rail.style.overflowX='auto';rail.style.scrollSnapType='x mandatory';rail.querySelectorAll('[aria-roledescription=slide]').forEach(slide=>slide.style.scrollSnapAlign='start');});

const backToTop=document.querySelector('[data-action="scroll-top"]');
if(backToTop){const sync=()=>{backToTop.style.opacity=window.scrollY>500?'1':'0';backToTop.style.pointerEvents=window.scrollY>500?'auto':'none'};window.addEventListener('scroll',sync,{passive:true});sync();}
// Keep the original RiseIn timing for server-rendered sections without hiding their initial HTML.
if(!matchMedia('(prefers-reduced-motion: reduce)').matches){
 const reveal=new IntersectionObserver(entries=>{for(const entry of entries){if(entry.isIntersecting){entry.target.animate([{opacity:0,transform:'translateY(24px) scale(.985)'},{opacity:1,transform:'none'}],{duration:700,easing:'cubic-bezier(.2,.7,.2,1)'});reveal.unobserve(entry.target);}}},{rootMargin:'0px 0px -64px 0px'});
 document.querySelectorAll('[data-reveal]').forEach(el=>{if(!el.closest('[data-island]'))reveal.observe(el)});
}
if(document.body.dataset.pageKind==='post'){
 const progress=document.querySelector('.fixed.h-1 > div');
 if(progress){const update=()=>{const height=document.documentElement.scrollHeight-innerHeight;progress.style.width=`${height>0?Math.min(100,Math.max(0,scrollY/height*100)):100}%`};window.addEventListener('scroll',update,{passive:true});update();}
}
