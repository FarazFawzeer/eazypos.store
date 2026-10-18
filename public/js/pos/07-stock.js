/* EazyPOS · Stock adjustment. */
function stockView(){
 const history=(data.stockHistory||[]).slice().sort((a,b)=>new Date(b.date)-new Date(a.date));
 return `<div class="page">${pageHead('Inventory','Stock Adjustment','Add or remove stock with a reason.')}
 <div class="card">
   <div class="searchbar">
     <div class="search-wrap" style="flex:1">
       <input class="input" id="stockSearch" placeholder="Search product / SKU" autocomplete="off" oninput="renderStockBox();renderStockSuggestions()">
       <div id="stockSearchSuggest" class="search-suggest" style="display:none"></div>
     </div>
   </div>
   <div id="stockBox"><div class="empty">Search for a product to adjust stock.</div></div>
 </div>
 <div class="card" style="margin-top:14px">
   <div class="row"><div><h2>Adjustment History</h2><div class="muted small">Latest stock movements</div></div></div>
   ${history.map(x=>`<div class="summary-row"><div><b>${esc(x.product)}</b><div class="muted small">${esc(x.reason)} · ${new Date(x.date).toLocaleString()}</div></div><b>${esc(x.change)}</b></div>`).join('')||'<div class="empty">No adjustments yet.</div>'}
 </div></div>`
}
function renderStockBox(){
 const box=document.getElementById('stockBox'); if(!box)return;
 const q=(document.getElementById('stockSearch')?.value||'').trim().toLowerCase();
 if(!q){box.innerHTML='<div class="empty">Search for a product to adjust stock.</div>';return}
 const list=data.products.filter(p=>(p.name+' '+(p.sku||'')).toLowerCase().includes(q)).slice(0,12);
 box.innerHTML=list.map(p=>`<div class="summary-row"><div><b>${esc(p.name)}</b><div class="muted">${esc(p.sku||'No SKU')} · ${p.stock} in stock</div></div><button type="button" class="btn" onclick="adjustStock('${String(p.id)}')">Adjust</button></div>`).join('')||'<div class="empty">No products found.</div>';
}
function renderStockSuggestions(){
 const el=document.getElementById('stockSearchSuggest'); if(!el)return;
 const q=(document.getElementById('stockSearch')?.value||'').trim().toLowerCase();
 if(!q){el.innerHTML='';el.style.display='none';return}
 const list=data.products.filter(p=>(p.name+' '+(p.sku||'')).toLowerCase().includes(q)).slice(0,8);
 el.innerHTML=list.map(p=>suggestionItem(p.name,`${p.sku||'No SKU'} · ${p.stock||0} in stock`,`selectStockProduct('${String(p.id)}')`)).join('')||'<div class="suggest-empty">No matching products</div>';
 el.style.display='block';
}
function selectStockProduct(id){
 const p=data.products.find(x=>String(x.id)===String(id));
 const input=document.getElementById('stockSearch'),el=document.getElementById('stockSearchSuggest');
 if(!p)return;
 if(input)input.value=p.name;
 if(el){el.innerHTML='';el.style.display='none';}
 renderStockBox();
 adjustStock(p.id);
}
function adjustStock(id){
 const p=data.products.find(x=>String(x.id)===String(id));
 if(!p){toast('Product not found');return;}
 state.adj='add';
 modal(`<div class="modal-head"><div><h2>${esc(p.name)}</h2><div class="muted">Current stock: ${p.stock}</div></div><button class="close" type="button" onclick="closeModal()">×</button></div>
 <div class="tabs"><button type="button" class="tab active" id="addAdj" onclick="setStockAdjustmentMode('add')">Add</button><button type="button" class="tab" id="remAdj" onclick="setStockAdjustmentMode('remove')">Remove</button></div>
 <div class="field"><label>QUANTITY</label><input class="input" id="adjQty" type="number" value="1" min="0.01" step="0.01"></div>
 <div class="field" style="margin-top:10px"><label>REASON</label><input class="input" id="adjReason" placeholder="Manual count / damage / correction"></div>
 <div class="modal-actions"><button type="button" class="btn" onclick="closeModal()">Cancel</button><button type="button" class="btn primary" onclick="saveAdjustment('${String(p.id)}')">Save Adjustment</button></div>`);
}
function setStockAdjustmentMode(mode){
 state.adj=mode;
 document.getElementById('addAdj')?.classList.toggle('active',mode==='add');
 document.getElementById('remAdj')?.classList.toggle('active',mode==='remove');
}
async function saveAdjustment(id){
 const p=data.products.find(x=>String(x.id)===String(id));
 if(!p){toast('Product not found');return;}
 const q=Number(document.getElementById('adjQty')?.value||0),mode=state.adj||'add',reason=(document.getElementById('adjReason')?.value||'').trim();
 if(!Number.isFinite(q)||q<=0){toast('Enter a valid quantity');return;}
 if(mode==='remove'&&q>Number(p.stock||0)){toast(`Cannot remove ${q}. Current stock is ${p.stock}.`);return;}
 const {data:result,error}=await eazyApi.rpc('adjust_stock',{p_business_id:account.businessId,p_product_id:p.id,p_adjustment_type:mode,p_quantity:q,p_reason:reason||'Manual adjustment'});
 if(error){apiError('Unable to adjust stock',error);return;}
 await loadBusinessData();
 closeModal();
 const oldStock=Number(result?.old_stock??p.stock),newStock=Number(result?.new_stock??p.stock);
 toast(`Stock updated ${oldStock} → ${newStock}`);
 state.adj='add';
 render();
}
