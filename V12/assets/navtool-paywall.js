/* NavTool Paywall: sperrt Premium-Module (unscharf + Code-Eingabe). Der Status kommt aus der Datenbank (my_plan),
   Reisen-Limit und Code-Prüfung laufen serverseitig (supabase/paywall.sql). */
(function(){
'use strict';
/* Welche Bereiche gesperrt sind: hier anpassen (Ordnername der Seite). Leer = nichts gesperrt. */
const PREMIUM=['calc','weather','navtext','inmarsat','simulator'];
const seg=location.pathname.split('/').filter(Boolean)[0]||'';
if(!PREMIUM.includes(seg))return;
const C=window.NT_SB||{},KEY='sb-'+String(C.url||'').replace(/^https?:\/\//,'').split('.')[0]+'-auth-token';
const L=(window.NT&&NT.ui)||'de',T=o=>o[L]||o.en||o.de;
const sess=()=>{try{return JSON.parse(localStorage.getItem(KEY))}catch(e){return null}};
async function token(){const s=sess();if(!s)return null;if(s.expires_at*1000-Date.now()>60000)return s.access_token;
 try{const r=await fetch(C.url+'/auth/v1/token?grant_type=refresh_token',{method:'POST',headers:{apikey:C.key,'Content-Type':'application/json'},body:JSON.stringify({refresh_token:s.refresh_token})});if(!r.ok)return null;const n=await r.json();localStorage.setItem(KEY,JSON.stringify(Object.assign({},s,n,{user:n.user||s.user})));return n.access_token}catch(e){return null}}
async function rpc(fn,body){const t=await token();if(!t)throw new Error('login');
 const r=await fetch(C.url+'/rest/v1/rpc/'+fn,{method:'POST',headers:{apikey:C.key,Authorization:'Bearer '+t,'Content-Type':'application/json'},body:JSON.stringify(body||{})});
 const j=await r.json().catch(()=>null);if(!r.ok)throw new Error((j&&(j.message||j.hint))||'Fehler');return j}
const css=document.createElement('style');
css.textContent='html.nt-lock body>*:not(#nt-pw):not(.nt-header):not(.nt-float):not(script):not(style){filter:blur(9px);pointer-events:none;user-select:none}html.nt-lock,html.nt-lock body{overflow:hidden}'+
'#nt-pw{position:fixed;inset:0;z-index:99999;display:grid;place-items:center;padding:18px;background:rgba(5,9,12,.55)}'+
'#nt-pw .c{width:min(440px,100%);padding:26px;border:1px solid #55e5d1;border-radius:22px;background:#071116;color:#e8f4f3;font:15px/1.5 Inter,system-ui,sans-serif;box-shadow:0 20px 60px rgba(0,0,0,.5)}'+
'#nt-pw h2{margin:0 0 8px;font-size:22px}#nt-pw p{margin:0 0 14px;color:#9db4b5}#nt-pw input{width:100%;box-sizing:border-box;padding:13px;border:1px solid rgba(85,229,209,.3);border-radius:12px;background:#05090c;color:#fff;font:600 15px monospace;text-transform:uppercase}'+
'#nt-pw button,#nt-pw a.b{display:block;width:100%;box-sizing:border-box;margin-top:10px;padding:13px;border:0;border-radius:12px;background:#55e5d1;color:#03211d;font:800 13px Inter,sans-serif;letter-spacing:.1em;text-transform:uppercase;text-align:center;text-decoration:none;cursor:pointer}'+
'#nt-pw a.s{display:block;margin-top:12px;text-align:center;color:#55e5d1;font-size:13px}#nt-pw .m{min-height:20px;margin-top:10px;font-size:13px;color:#ff9b9b}#nt-pw .m.ok{color:#55e5d1}';
document.head.appendChild(css);
function lock(loggedIn){
 document.documentElement.classList.add('nt-lock');
 const d=document.createElement('div');d.id='nt-pw';d.setAttribute('role','dialog');d.setAttribute('aria-modal','true');
 d.innerHTML='<div class="c"><h2>'+T({de:'Premium-Bereich',en:'Premium area',fil:'Premium na seksyon'})+'</h2><p>'+
  T({de:'Dieser Bereich ist in der Gratis-Version gesperrt. Gib deinen Freischalt-Code ein, den du nach dem Kauf per Mail erhalten hast. Er ist einmal einlösbar.',en:'This area is locked in the free version. Enter the unlock code you received by email after purchase. It can be redeemed once.',fil:'Naka-lock ang seksyong ito sa libreng bersyon. Ilagay ang unlock code na natanggap mo sa email pagkatapos bumili. Isang beses lang ito magagamit.'})+'</p>'+
  (loggedIn?'<input id="nt-pc" autocomplete="off" spellcheck="false" placeholder="2026-10042-12-31-MUSTERMANN"><button type="button" id="nt-pg">'+T({de:'Code einlösen',en:'Redeem code',fil:'I-redeem ang code'})+'</button><div class="m" id="nt-pm" role="alert"></div>'
   :'<a class="b" href="/konto/?m=login&next='+encodeURIComponent(location.pathname)+'">'+T({de:'Anmelden, um Code einzulösen',en:'Sign in to redeem a code',fil:'Mag-sign in para i-redeem ang code'})+'</a>')+
  '<a class="s" href="/">← '+T({de:'Zur Startseite',en:'Home',fil:'Home'})+'</a></div>';
 document.body.appendChild(d);
 const b=d.querySelector('#nt-pg');if(!b)return;
 const go=async()=>{const m=d.querySelector('#nt-pm');m.className='m';m.textContent='';const v=d.querySelector('#nt-pc').value.trim();if(!v)return;b.disabled=true;
  try{const r=await rpc('redeem_code',{p_code:v});sessionStorage.removeItem('nt-plan');m.className='m ok';m.textContent=T({de:'Freigeschaltet!',en:'Unlocked!',fil:'Na-unlock na!'});setTimeout(()=>location.reload(),600)}
  catch(e){m.textContent=e.message==='login'?T({de:'Bitte neu anmelden.',en:'Please sign in again.',fil:'Mag-sign in muli.'}):e.message;b.disabled=false}};
 b.onclick=go;d.querySelector('#nt-pc').addEventListener('keydown',e=>{if(e.key==='Enter')go()});
}
async function plan(){
 try{const c=JSON.parse(sessionStorage.getItem('nt-plan')||'null');if(c&&c.u===(sess()&&sess().user&&sess().user.id)&&Date.now()-c.t<300000)return c.p}catch(e){}
 const s=sess();if(!s||!s.user)return{premium:false,login:false};
 try{const p=await rpc('my_plan');sessionStorage.setItem('nt-plan',JSON.stringify({u:s.user.id,t:Date.now(),p:p}));return Object.assign({login:true},p)}
 catch(e){return{premium:false,login:true}}
}
/* Standard: gesperrt, bis die Datenbank "premium" bestätigt (kein kurzes Aufblitzen der Inhalte) */
document.documentElement.classList.add('nt-lock');
const go=()=>plan().then(p=>{if(p.premium){document.documentElement.classList.remove('nt-lock');return}document.documentElement.classList.remove('nt-lock');lock(p.login!==false&&!!sess())});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',go);else go();
})();
