/* EazyPOS · Mobile table labels.
 *
 * On phones, tables are shown as stacked cards (see polish.css). Each cell
 * needs its column name, so this copies the header text onto every cell as
 * data-label, whenever a screen renders or a list refreshes.
 */
(function(){
 function labelTables(root){
  (root||document).querySelectorAll('table.table').forEach(table=>{
   const heads=[...table.querySelectorAll('thead th')].map(th=>th.textContent.trim());
   table.querySelectorAll('tbody tr').forEach(tr=>{
    [...tr.children].forEach((td,i)=>{
     if(td.hasAttribute('colspan')){td.classList.add('cell-full');return}
     td.setAttribute('data-label',heads[i]||'');
     if(i===0)td.classList.add('cell-title');
     if(!heads[i]||td.querySelector('.btn'))td.classList.add('cell-actions');
     // Keep multi-line values (name + email, etc.) together as one block.
     const parts=[...td.childNodes].filter(n=>n.nodeType===1||n.textContent.trim()!=='');
     if(i>0&&parts.length>1&&!td.querySelector(':scope > .cell-value')){
      const wrap=document.createElement('div');wrap.className='cell-value';
      while(td.firstChild)wrap.appendChild(td.firstChild);
      td.appendChild(wrap);
     }
    });
   });
   table.classList.add('table-stack');
  });
 }
 const page=document.getElementById('page');
 if(page){
  let queued=false;
  new MutationObserver(()=>{if(queued)return;queued=true;requestAnimationFrame(()=>{queued=false;labelTables(page)})}).observe(page,{childList:true,subtree:true});
 }
 window.labelTables=labelTables;
})();

/* Full screen (POS toolbar button) hides the sidebar and bottom bar;
 * leaving full screen, by button or Esc, brings them back. */
(function(){
 const sync=()=>document.body.classList.toggle('pos-fullscreen',!!(document.fullscreenElement||document.webkitFullscreenElement));
 document.addEventListener('fullscreenchange',sync);
 document.addEventListener('webkitfullscreenchange',sync);
 sync();
})();

/* Phone POS: floating cart button. Shows the item count and total, jumps
 * to the cart, and hides itself while the cart is already on screen. */
(function(){
 let el=null,observer=null,watched=null,lastCount=0;
 function ensure(){
  if(el)return el;
  el=document.createElement('button');
  el.type='button';el.id='posCartJump';el.className='pos-cart-jump';
  el.addEventListener('click',()=>{document.querySelector('.pos-screen-cart')?.scrollIntoView({behavior:'smooth',block:'start'})});
  document.body.appendChild(el);
  return el;
 }
 function update(){
  if(typeof state==='undefined'||state.view!=='pos'){if(el)el.classList.remove('show');return}
  const cart=state.cart||[];
  const count=cart.reduce((a,i)=>a+Number(i.qty||0),0);
  const subtotal=cart.reduce((a,i)=>a+Number(i.qty||0)*Number(i.price||0),0);
  const total=Math.max(0,subtotal-(typeof getPOSDiscountAmount==='function'?getPOSDiscountAmount(subtotal):0));
  const b=ensure();
  b.innerHTML=`<span class="pcj-icon">${icon('cart')}<span class="pcj-badge">${count}</span></span><span class="pcj-text"><b>${count?money(total):'Cart is empty'}</b><small>${count?count+(count===1?' item':' items'):'Add products'}</small></span><span class="pcj-cta">View cart ${icon('chevronDown','i-sm')}</span>`;
  b.setAttribute('aria-label',count?`View cart, ${count} items, ${money(total)}`:'View cart');
  if(count>lastCount){b.classList.remove('bump');void b.offsetWidth;b.classList.add('bump')}
  lastCount=count;
  b.classList.add('show');
  const target=document.querySelector('.pos-screen-cart');
  if(target&&target!==watched){
   observer?.disconnect();
   observer=new IntersectionObserver(entries=>{entries.forEach(e=>b.classList.toggle('cart-visible',e.isIntersecting&&e.intersectionRatio>0.2))},{threshold:[0,0.2,0.5]});
   observer.observe(target);watched=target;
  }
 }
 const page=document.getElementById('page');
 if(page){let q=false;new MutationObserver(()=>{if(q)return;q=true;requestAnimationFrame(()=>{q=false;update()})}).observe(page,{childList:true,subtree:true})}
 window.addEventListener('popstate',()=>requestAnimationFrame(update));
})();
