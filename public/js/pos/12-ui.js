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
