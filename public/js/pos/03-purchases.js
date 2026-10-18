/* EazyPOS · Purchases: list, add/edit, suppliers picker, quick product and purchase returns. */
function purchaseListView(){return `<div class="page">${pageHead('Purchases','Purchase List','Suppliers, purchase totals and payment status.','<button class="btn primary" onclick="go(\'addPurchase\')">＋ Add Purchase</button>')}<div class="grid grid3 stats"><div class="card"><div class="stat-label">Purchases</div><div class="stat-value">${data.purchases.length}</div></div><div class="card"><div class="stat-label">Total Purchase</div><div class="stat-value">${money(data.purchases.reduce((a,p)=>a+p.total,0))}</div></div><div class="card"><div class="stat-label">Outstanding</div><div class="stat-value">${money(0)}</div></div></div><div class="card" style="margin-top:14px"><div class="table-wrap"><table class="table"><thead><tr><th>Purchase</th><th>Supplier</th><th>Date</th><th>Payment</th><th>Total</th></tr></thead><tbody>${data.purchases.slice().reverse().map(p=>`<tr><td><b>${p.number}</b></td><td>${esc(p.supplier)}</td><td>${new Date(p.date).toLocaleString()}</td><td>${p.payment}</td><td>${money(p.total)}</td></tr>`).join('')}</tbody></table></div></div></div>`}
function purchaseFormView(){
 if(!state.purchaseCart)state.purchaseCart=[];
 if(!state.purchasePayment)state.purchasePayment='Cash';
 if(state.purchasePaid===undefined)state.purchasePaid='';
 if(!state.purchaseDate)state.purchaseDate=today();
 const rows=state.purchaseCart;
 const subtotal=rows.reduce((a,r)=>a+(Number(r.qty)||0)*(Number(r.cost)||0)-(Number(r.discount)||0)+(Number(r.tax)||0),0);
 const discount=Number(state.purchaseDiscount||0),tax=Number(state.purchaseTax||0),shipping=Number(state.purchaseShipping||0),other=Number(state.purchaseOther||0);
 const total=Math.max(0,subtotal-discount+tax+shipping+other);
 const paid=state.purchasePayment==='Credit'?(state.purchasePaid===''?0:Number(state.purchasePaid||0)):total;
 return `<div class="page">${pageHead('Purchases',state.editingPurchaseId?'Edit Purchase':'Add Purchase',state.editingPurchaseId?'Update the purchase safely — stock, supplier balance and register will be recalculated.':'Supplier → products → payment → save purchase.','<button class="btn" onclick="go(\'purchases\')">← Back</button>')}
 <div class="grid grid2">
  <div class="card">
   <div class="grid grid2">
    <div class="field"><label>SUPPLIER</label><div class="picker-toolbar"><div class="search-wrap supplier-search-wrap" style="flex:1;min-width:180px"><input class="input" id="purchaseSupplierSearch" placeholder="Search a supplier..." value="${esc(state.purchaseSupplierSearch||'')}" autocomplete="off" oninput="state.purchaseSupplierSearch=this.value;renderSupplierPicker()"><div id="purchaseSupplierSuggest" class="search-suggest supplier-search-suggest" style="display:none"></div></div><button class="btn primary" onclick="addSupplier(true)">＋ Add Supplier</button></div><div id="purchaseSupplierSelected" class="overlay-note" style="margin-top:8px">${state.purchaseSupplier?(function(){const s=data.suppliers.find(x=>String(x.id)===String(state.purchaseSupplier));return s?'Selected: '+esc(s.name):'No supplier selected';})():'No supplier selected'}</div></div>
    <div class="field"><label>PURCHASE DATE</label><input class="input" id="purchaseDate" type="date" value="${esc(state.purchaseDate)}" onchange="state.purchaseDate=this.value"></div>
   </div>
   <div class="searchbar" style="margin-top:12px"><div class="search-wrap" style="flex:1"><input class="input" id="purchaseProductSearch" placeholder="Search product / SKU" oninput="renderPurchaseProductSuggestions()" onkeydown="if(event.key==='Enter')addPurchaseProduct()"><div id="purchaseProductSuggest" class="search-suggest"></div></div><button class="btn" onclick="addPurchaseProduct()">＋ Add</button><button class="btn primary" onclick="addProductQuick(true)">＋ Add New Product</button></div>
   <div style="margin-top:12px">${rows.map((r,i)=>`<div class="card" style="margin-bottom:10px;padding:12px"><div class="row"><div><b>${esc(r.name)}</b><div class="muted small">${esc(r.sku||'')} · Current cost ${money(r.originalCost||r.cost)}</div></div><button class="btn danger" onclick="removePurchaseRow(${i})">×</button></div><div class="grid grid3" style="margin-top:10px"><div class="field"><label>QTY</label><input class="input" type="number" min="0.001" step="any" value="${r.qty}" onchange="updatePurchaseRow(${i},'qty',this.value)"></div><div class="field"><label>UNIT COST</label><input class="input" type="number" min="0" step="any" value="${r.cost}" onchange="updatePurchaseRow(${i},'cost',this.value)"></div><div class="field"><label>SELLING PRICE</label><input class="input" type="number" min="0" step="any" value="${r.sellingPrice||0}" onchange="updatePurchaseRow(${i},'sellingPrice',this.value)"></div></div><div class="grid grid2" style="margin-top:8px"><div class="field"><label>ITEM DISCOUNT</label><input class="input" type="number" min="0" step="any" value="${r.discount||0}" onchange="updatePurchaseRow(${i},'discount',this.value)"></div><div class="field"><label>ITEM TAX</label><input class="input" type="number" min="0" step="any" value="${r.tax||0}" onchange="updatePurchaseRow(${i},'tax',this.value)"></div></div><div class="summary-row" style="margin-top:8px"><span>Line Total</span><b>${money((Number(r.qty)||0)*(Number(r.cost)||0)-(Number(r.discount)||0)+(Number(r.tax)||0))}</b></div></div>`).join('')||'<div class="empty">Add products to this purchase.</div>'}</div>
  </div>
  <div class="card"><h2>Purchase Summary</h2><div class="summary-row"><span>Items</span><b>${rows.reduce((a,r)=>a+(Number(r.qty)||0),0)}</b></div><div class="summary-row"><span>Subtotal</span><b>${money(subtotal)}</b></div><div class="grid grid2" style="margin-top:10px"><div class="field"><label>DISCOUNT</label><input class="input" type="number" min="0" step="any" value="${discount}" onchange="state.purchaseDiscount=this.value;render()"></div><div class="field"><label>TAX</label><input class="input" type="number" min="0" step="any" value="${tax}" onchange="state.purchaseTax=this.value;render()"></div><div class="field"><label>SHIPPING</label><input class="input" type="number" min="0" step="any" value="${shipping}" onchange="state.purchaseShipping=this.value;render()"></div><div class="field"><label>OTHER CHARGE</label><input class="input" type="number" min="0" step="any" value="${other}" onchange="state.purchaseOther=this.value;render()"></div></div><div class="summary-row"><span>Grand Total</span><b>${money(total)}</b></div><div class="tabs"><button class="tab ${state.purchasePayment==='Cash'?'active':''}" onclick="state.purchasePayment='Cash';state.purchasePaid='';render()">Cash</button><button class="tab ${state.purchasePayment==='Cheque'?'active':''}" onclick="state.purchasePayment='Cheque';state.purchasePaid='';render()">Cheque</button><button class="tab ${state.purchasePayment==='Credit'?'active':''}" onclick="state.purchasePayment='Credit';state.purchasePaid='';render()">Credit</button></div>${state.purchasePayment==='Credit'?`<div class="field" style="margin-top:10px"><label>AMOUNT PAID NOW</label><input class="input" id="purchasePaid" type="number" min="0" step="any" value="${state.purchasePaid}" oninput="state.purchasePaid=this.value"></div>`:`<div class="overlay-note" style="margin-top:10px">${state.purchasePayment} payment will be recorded for ${money(total)}.</div>`}<div class="summary-row" style="margin-top:8px"><span>Amount Due</span><b>${money(Math.max(0,total-(Number(paid)||0)))}</b></div><button class="btn primary" style="width:100%;margin-top:14px" onclick="savePurchase()">${state.editingPurchaseId?'Update Purchase':'Save Purchase'}</button></div>
 </div></div>`
}
function suggestionItem(title,meta,onClick){return `<button type="button" class="suggest-item" onclick="${esc(onClick)}"><span class="suggest-main"><b>${esc(title)}</b><span class="suggest-meta">${esc(meta||'')}</span></span><span>›</span></button>`}
function renderGlobalSuggestions(){
 const el=document.getElementById('globalSearchSuggest'); if(!el)return; const q=(document.getElementById('globalSearch')?.value||'').trim().toLowerCase();
 if(!q){el.style.display='none';return}
 const ps=data.products.filter(p=>(p.name+' '+(p.sku||'')+' '+(p.barcode||'')).toLowerCase().includes(q)).slice(0,5);
 const cs=data.customers.filter(c=>(c.name+' '+(c.phone||'')+' '+(c.email||'')).toLowerCase().includes(q)).slice(0,4);
 const ss=data.suppliers.filter(s=>(s.name+' '+(s.phone||'')+' '+(s.email||'')).toLowerCase().includes(q)).slice(0,4);
 let html=ps.map(p=>suggestionItem(p.name,`${p.sku||'No SKU'} · ${money(p.sell||0)}`,`state.productQuery='${String(p.name).replace(/'/g,"\\'")}';go('products');document.getElementById('globalSearch').value='';document.getElementById('globalSearchSuggest').style.display='none'`)).join('');
 html+=cs.map(c=>suggestionItem(c.name,`Customer · ${c.phone||'No phone'}`,`go('customers');document.getElementById('globalSearch').value='';document.getElementById('globalSearchSuggest').style.display='none'`)).join('');
 html+=ss.map(s=>suggestionItem(s.name,`Supplier · ${s.phone||'No phone'}`,`go('suppliers');document.getElementById('globalSearch').value='';document.getElementById('globalSearchSuggest').style.display='none'`)).join('');
 el.innerHTML=html||'<div class="suggest-empty">No matching results</div>';el.style.display='block';
}
function renderPurchaseProductSuggestions(){
 const el=document.getElementById('purchaseProductSuggest'); if(!el)return; const q=(document.getElementById('purchaseProductSearch')?.value||'').trim().toLowerCase();
 if(!q){el.innerHTML='';return}
 const list=data.products.filter(p=>(p.name+' '+(p.sku||'')+' '+(p.barcode||'')).toLowerCase().includes(q)).slice(0,8);
 el.innerHTML=list.map(p=>suggestionItem(p.name,`${p.sku||'No SKU'} · Stock ${p.stock||0} · Cost ${money(p.cost||0)}`,`addPurchaseProductById('${p.id}')`)).join('')||'<div class="suggest-empty">No matching products</div>';
}
function addPurchaseProductById(id){const p=data.products.find(x=>String(x.id)===String(id));if(!p)return;if(!state.purchaseCart)state.purchaseCart=[];let r=state.purchaseCart.find(x=>x.productId===p.id);if(r)r.qty=Number(r.qty||0)+1;else state.purchaseCart.push({productId:p.id,name:p.name,sku:p.sku||'',qty:1,cost:Number(p.cost||0),originalCost:Number(p.cost||0),sellingPrice:Number(p.sell||0),discount:0,tax:0});state.purchaseProductSearch='';render()}
function renderPOSCustomerSuggestions(){const el=document.getElementById('posCustomerSuggest');if(!el)return;const q=(document.getElementById('posCustomerSearch')?.value||'').trim().toLowerCase();if(!q){el.innerHTML='';return}const list=data.customers.filter(c=>(c.name+' '+(c.phone||'')+' '+(c.email||'')).toLowerCase().includes(q)).slice(0,8);el.innerHTML=list.map(c=>suggestionItem(c.name,`${c.phone||'No phone'} · ${(c.customerType||'regular')}`,`selectPOSCustomer('${c.id}')`)).join('')||'<div class="suggest-empty">No matching customers</div>'}
function renderStockSuggestions(){const el=document.getElementById('stockSearchSuggest');if(!el)return;const q=(document.getElementById('stockSearch')?.value||'').trim().toLowerCase();if(!q){el.innerHTML='';return}const list=data.products.filter(p=>(p.name+' '+(p.sku||'')).toLowerCase().includes(q)).slice(0,8);el.innerHTML=list.map(p=>suggestionItem(p.name,`${p.sku||'No SKU'} · ${p.stock||0} in stock`,`document.getElementById('stockSearch').value='${String(p.name).replace(/'/g,"\\'")}';renderStockBox()`)).join('')||'<div class="suggest-empty">No matching products</div>'}

function renderSupplierPicker(){
 const sug=document.getElementById('purchaseSupplierSuggest');
 if(!sug)return;
 const q=(document.getElementById('purchaseSupplierSearch')?.value ?? state.purchaseSupplierSearch ?? '').trim().toLowerCase();
 state.purchaseSupplierSearch=q;
 if(!q){
   sug.innerHTML='';
   sug.style.display='none';
   return;
 }
 const list=data.suppliers
   .filter(s=>(s.name+' '+(s.phone||'')+' '+(s.email||'')).toLowerCase().includes(q))
   .slice(0,8);
 const html=list.map(s=>suggestionItem(
   s.name,
   `${s.phone||s.email||'Supplier'} · Balance ${money(s.balance||0)}`,
   `choosePurchaseSupplier('${s.id}')`
 )).join('');
 sug.innerHTML=html || '<div class="suggest-empty">No suppliers found</div>';
 sug.style.display='block';
}

function choosePurchaseSupplier(id){const s=data.suppliers.find(x=>String(x.id)===String(id));if(!s)return;state.purchaseSupplier=s.id;state.purchaseSupplierSearch='';render();setTimeout(()=>{const el=document.getElementById('purchaseSupplierSearch');if(el)el.value='';},0)}
async function addSupplier(fromPurchase=false){
 modal(`<div class="modal-head"><h2>Add New Supplier</h2><button class="close" onclick="closeModal()">×</button></div><div class="form-grid"><div class="field"><label>SUPPLIER NAME *</label><input class="input" id="suName" placeholder="Supplier / company name"></div><div class="field"><label>CONTACT PERSON</label><input class="input" id="suContact" placeholder="Contact person"></div><div class="field"><label>PHONE</label><input class="input" id="suPhone" placeholder="Phone number"></div><div class="field"><label>EMAIL</label><input class="input" id="suEmail" type="email" placeholder="Email address"></div><div class="field"><label>OPENING BALANCE</label><input class="input" id="suBalance" type="number" min="0" step="0.01" value="0"></div><div class="field" style="grid-column:1/-1"><label>ADDRESS</label><textarea class="input" id="suAddress" rows="2" placeholder="Supplier address"></textarea></div><div class="field" style="grid-column:1/-1"><label>NOTES</label><textarea class="input" id="suNotes" rows="2" placeholder="Notes"></textarea></div></div><button class="btn primary" style="width:100%;margin-top:14px" onclick="saveSupplier(${fromPurchase?'true':'false'})">Save Supplier</button>`);
}
async function saveSupplier(fromPurchase=false){
 const name=document.getElementById('suName').value.trim();
 if(!name){toast('Supplier name required');return}
 const opening=Number(document.getElementById('suBalance').value||0);
 if(!Number.isFinite(opening)||opening<0){toast('Enter a valid opening balance');return}
 const payload={business_id:account.businessId,name,contact_person:document.getElementById('suContact').value.trim()||null,phone:document.getElementById('suPhone').value.trim()||null,email:document.getElementById('suEmail').value.trim()||null,address:document.getElementById('suAddress').value.trim()||null,opening_balance:opening,current_balance:opening,notes:document.getElementById('suNotes').value.trim()||null,is_active:true};
 const {data:row,error}=await eazyApi.post('/api/suppliers',payload);
 if(error){apiError('Unable to save supplier',error);return}
 await loadBusinessData();closeModal();toast('Supplier added successfully');
 if(fromPurchase){state.purchaseSupplier=row.id;state.purchaseSupplierSearch='';render();setTimeout(renderSupplierPicker,0)}else{go('suppliers')}
}
function addProductQuick(fromPurchase=false){
 modal(`<div class="modal-head"><h2>Quick Add Product</h2><button class="close" onclick="closeModal()">×</button></div><div class="form-grid"><div class="field"><label>PRODUCT NAME *</label><input class="input" id="qpName" placeholder="Product name"></div><div class="field"><label>SKU / BARCODE</label><input class="input" id="qpSku" value="SKU-${String(Date.now()).slice(-6)}"></div><div class="field"><label>COST PRICE *</label><input class="input" id="qpCost" type="number" min="0" step="0.01" value="0"></div><div class="field"><label>SELLING PRICE *</label><input class="input" id="qpSell" type="number" min="0" step="0.01" value="0"></div><div class="field"><label>OPENING STOCK</label><input class="input" id="qpStock" type="number" min="0" step="any" value="0"></div><div class="field"><label>LOW STOCK ALERT</label><input class="input" id="qpLow" type="number" min="0" step="any" value="5"></div></div><div class="overlay-note">You can add category, brand, unit and other details later from Products → Edit Product.</div><button class="btn primary" style="width:100%;margin-top:14px" onclick="saveQuickProduct(${fromPurchase?'true':'false'})">Save Product</button>`);
}
async function saveQuickProduct(fromPurchase=false){
 const name=document.getElementById('qpName').value.trim();
 const sku=document.getElementById('qpSku').value.trim()||('SKU-'+String(Date.now()).slice(-6));
 const cost=Number(document.getElementById('qpCost').value||0),sell=Number(document.getElementById('qpSell').value||0),stock=Number(document.getElementById('qpStock').value||0),low=Number(document.getElementById('qpLow').value||5);
 if(!name){toast('Product name required');return}
 if([cost,sell,stock,low].some(v=>!Number.isFinite(v)||v<0)){toast('Enter valid product numbers');return}
 const {data:row,error}=await eazyApi.post('/api/products/quick',{name,sku,cost_price:cost,selling_price:sell,stock_quantity:stock,low_stock_alert:low});
 if(error){apiError('Unable to add product',error);return}
 /* Opening stock movement is recorded by the server. */
 await loadBusinessData();closeModal();toast('Product added successfully');
 if(fromPurchase){const p=data.products.find(x=>String(x.id)===String(row.id))||{id:row.id,name:row.name,sku:row.sku||'',cost:Number(row.cost_price||0),sell:Number(row.selling_price||0),stock:Number(row.stock_quantity||0)};state.purchaseCart=state.purchaseCart||[];state.purchaseCart.push({productId:p.id,name:p.name,sku:p.sku||'',qty:1,cost:Number(p.cost||0),originalCost:Number(p.cost||0),sellingPrice:Number(p.sell||0),discount:0,tax:0});render();}else{go('products')}
}

function addPurchaseProduct(){let q=(document.getElementById('purchaseProductSearch').value||'').trim().toLowerCase();if(!q){toast('Search for a product');return}let p=data.products.find(x=>(x.name+' '+x.sku+' '+(x.barcode||'')).toLowerCase().includes(q));if(!p){toast('Product not found');return}if(!state.purchaseCart)state.purchaseCart=[];let r=state.purchaseCart.find(x=>x.productId===p.id);if(r)r.qty=Number(r.qty||0)+1;else state.purchaseCart.push({productId:p.id,name:p.name,sku:p.sku||'',qty:1,cost:Number(p.cost||0),originalCost:Number(p.cost||0),sellingPrice:Number(p.sell||0),discount:0,tax:0});document.getElementById('purchaseProductSearch').value='';render()}
function updatePurchaseRow(i,key,value){if(!state.purchaseCart?.[i])return;const n=Number(value);if(!Number.isFinite(n)||n<0){toast('Enter a valid number');return}state.purchaseCart[i][key]=key==='qty'&&n===0?1:n;render()}
function removePurchaseRow(i){state.purchaseCart.splice(i,1);render()}
function purchaseItemsForEdit(p){return (p.items||[]).map(i=>{const prod=data.products.find(x=>String(x.id)===String(i.productId));return {productId:i.productId,name:prod?.name||'Product',sku:prod?.sku||'',qty:i.qty,cost:i.cost,originalCost:i.cost,sellingPrice:i.sellingPrice,discount:i.discount||0,tax:i.tax||0}})}
function viewPurchase(id){const p=data.purchases.find(x=>String(x.id)===String(id));if(!p)return;modal(`<div class="modal-head"><div><div class="eyebrow">PURCHASE</div><h2>${esc(p.number)}</h2><div class="muted small">${new Date(p.date).toLocaleString()} · ${esc(p.status)}</div></div><button class="close" onclick="closeModal()">×</button></div><div class="summary-row"><span>Supplier</span><b>${esc(p.supplier)}</b></div><div class="summary-row"><span>Payment</span><b>${esc(p.payment)}</b></div><div class="summary-row"><span>Total</span><b>${money(p.total)}</b></div><div class="summary-row"><span>Paid</span><b>${money(p.amountPaid)}</b></div><div class="summary-row"><span>Due</span><b>${money(p.amountDue)}</b></div><div class="divider"></div><h3>Items</h3>${(p.items||[]).map(i=>{const pr=data.products.find(x=>String(x.id)===String(i.productId));return `<div class="summary-row"><span>${esc(pr?.name||'Product')} × ${i.qty}</span><b>${money(i.lineTotal||((i.qty||0)*(i.cost||0)-(i.discount||0)+(i.tax||0)))}</b></div>`}).join('')||'<div class="empty">No items.</div>'}<div class="modal-actions" style="margin-top:14px"><button class="btn" onclick="closeModal()">Close</button>${p.status!=='cancelled'?`<button class="btn" onclick="closeModal();returnPurchase('${p.id}')">Return</button><button class="btn" onclick="closeModal();editPurchase('${p.id}')">Edit</button><button class="btn danger" onclick="closeModal();deletePurchase('${p.id}')">Delete</button>`:''}</div>`)}
function editPurchase(id){const p=data.purchases.find(x=>String(x.id)===String(id));if(!p)return;state.editingPurchaseId=p.id;state.purchaseSupplier=p.supplierId||'';state.purchaseSupplierSearch='';state.purchaseCart=purchaseItemsForEdit(p);state.purchasePayment=p.payment||'Cash';state.purchasePaid=p.payment==='Credit'?String(p.amountPaid||0):'';state.purchaseDate=(p.date||today()).slice(0,10);state.purchaseDiscount=0;state.purchaseTax=0;state.purchaseShipping=0;state.purchaseOther=0;go('addPurchase')}
function deletePurchase(id){const p=data.purchases.find(x=>String(x.id)===String(id));if(!p)return;modal(`<div class="modal-head"><div><h2>Delete Purchase?</h2><div class="muted small">${esc(p.number)} · ${esc(p.supplier)}</div></div><button class="close" onclick="closeModal()">×</button></div><div class="danger-note">This will cancel the purchase and reverse its stock, supplier balance and cash-register effect where applicable. The purchase record remains in history for audit purposes.</div><div class="modal-actions"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn danger" onclick="confirmDeletePurchase('${p.id}')">Delete Purchase</button></div>`)}
async function confirmDeletePurchase(id){if(!account?.businessId){toast('Business is not ready');return}const {data:res,error}=await eazyApi.rpc('delete_purchase',{p_business_id:account.businessId,p_purchase_id:id});if(error){apiError('Unable to delete purchase',error);return}await loadBusinessData();closeModal();toast('Purchase deleted and reversed');render()}

function openRegisterForPurchase(){
 modal(`<div class="modal-head"><div><div class="eyebrow">Cash Register</div><h2>Open Register to Continue</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="muted small" style="margin-bottom:14px">A cash purchase must be linked to an open register so the cash movement is recorded correctly.</div><div class="field"><label>OPENING CASH</label><input class="input" id="purchaseOpeningCash" type="number" min="0" value="10000"></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn primary" onclick="confirmOpenRegisterForPurchase()">Open Register & Continue</button></div>`)
}
async function confirmOpenRegisterForPurchase(){
 const amount=Number(document.getElementById('purchaseOpeningCash')?.value||0);
 if(!account?.businessId){toast('Business is not ready');return}
 if(!Number.isFinite(amount)||amount<0){toast('Enter a valid opening cash amount');return}
 const {data:row,error}=await eazyApi.post('/api/registers',{opening_cash:amount});
 if(error){apiError('Unable to open register',error);return}
 await loadBusinessData();
 closeModal();
 toast('Register opened — continuing purchase');
 setTimeout(()=>savePurchase(),150);
}

async function savePurchase(){
 if(!account?.businessId){toast('Business is not ready');return}
 const supplier=state.purchaseSupplier||'';
 const date=document.getElementById('purchaseDate')?.value||state.purchaseDate||today();
 const rows=state.purchaseCart||[];
 if(!supplier||!rows.length){toast('Select supplier and add products');return}
 state.purchaseSupplier=supplier;state.purchaseDate=date;
 const payment=(state.purchasePayment||'Cash').toLowerCase().replace(' ','_');
 const subtotal=rows.reduce((a,r)=>a+(Number(r.qty)||0)*(Number(r.cost)||0)-(Number(r.discount)||0)+(Number(r.tax)||0),0);
 const discount=Number(state.purchaseDiscount||0),tax=Number(state.purchaseTax||0),shipping=Number(state.purchaseShipping||0),other=Number(state.purchaseOther||0);
 const total=Math.max(0,subtotal-discount+tax+shipping+other);
 const amountPaid=payment==='credit'?Number(state.purchasePaid||0):total;
 if(!Number.isFinite(amountPaid)||amountPaid<0){toast('Enter a valid amount paid');return}
 if(payment==='credit'&&amountPaid>total){toast('Amount paid cannot exceed total');return}
 if(payment!=='credit'&&amountPaid!==total){toast('Payment amount must equal total');return}
 const items=rows.map(r=>({product_id:r.productId,quantity:Number(r.qty)||0,unit_cost:Number(r.cost)||0,selling_price:Number(r.sellingPrice||0),discount:Number(r.discount||0),tax:Number(r.tax||0)}));
 if(payment==='cash'&&!getOpenRegister()){openRegisterForPurchase();return}
 const registerId=payment==='cash'?(getOpenRegister()?.id||null):null;
 const common={p_business_id:account.businessId,p_supplier_id:supplier,p_items:items,p_payment_method:payment,p_amount_paid:amountPaid,p_discount:discount,p_tax:tax,p_shipping_charge:shipping,p_other_charge:other,p_purchase_date:new Date(date+'T00:00:00').toISOString(),p_notes:null,p_register_id:registerId};
 const rpcName=state.editingPurchaseId?'update_purchase':'create_purchase';
 const args=state.editingPurchaseId?{...common,p_purchase_id:state.editingPurchaseId}:common;
 const editing=!!state.editingPurchaseId;
 const {data:res,error}=await eazyApi.rpc(rpcName,args);
 if(error){apiError(state.editingPurchaseId?'Unable to update purchase':'Unable to save purchase',error);return}
 await loadBusinessData();
 state.editingPurchaseId=null;state.purchaseCart=[];state.purchaseSupplier='';state.purchaseSupplierSearch='';state.purchasePaid='';state.purchaseDiscount=0;state.purchaseTax=0;state.purchaseShipping=0;state.purchaseOther=0;
 toast(editing?'Purchase updated':'Purchase saved');go('purchases')
}

function returnPurchase(id){state.purchaseReturnPurchaseId=id;state.purchaseReturnRefundMethod='credit';state.purchaseReturnNotes='';go('purchaseReturns')}
function purchaseReturnView(){
 const p=data.purchases.find(x=>String(x.id)===String(state.purchaseReturnPurchaseId));
 if(p){
  if(p.status==='cancelled')return `<div class="page">${pageHead('Purchases','Purchase Return','Cancelled purchases cannot be returned.','<button class="btn" onclick="go(\'purchases\')">← Purchases</button>')}<div class="card"><div class="empty">This purchase is cancelled and cannot be returned.</div></div></div>`;
  const returnedByProduct={};
  data.purchaseReturns.filter(r=>r.purchaseId===p.id&&r.status==='completed').forEach(r=>(r.items||[]).forEach(i=>{returnedByProduct[i.productId]=(returnedByProduct[i.productId]||0)+Number(i.qty||0)}));
  const rows=(p.items||[]).map((i,idx)=>{const returned=Number(returnedByProduct[i.productId]||0);const remaining=Math.max(0,Number(i.qty||0)-returned);const pr=data.products.find(x=>String(x.id)===String(i.productId));return {i,idx,pr,returned,remaining}}).filter(x=>x.remaining>0);
  if(!rows.length)return `<div class="page">${pageHead('Purchases','Purchase Return','No remaining quantities are available for return.','<button class="btn" onclick="go(\'purchases\')">← Purchases</button>')}<div class="card"><div class="empty">All quantities from this purchase have already been returned.</div></div></div>`;
  return `<div class="page">${pageHead('Purchases','Purchase Return','Select the products and quantities to return.','<button class="btn" onclick="go(\'purchases\')">← Purchases</button>')}
  <div class="grid grid2">
   <div class="card"><div class="row"><div><h2>${esc(p.number)}</h2><div class="muted">${esc(p.supplier)} · ${new Date(p.date).toLocaleString()}</div></div><span class="pill good">Original ${money(p.total)}</span></div>
    <div style="margin-top:16px">${rows.map(x=>`<div class="summary-row" style="gap:12px"><div style="min-width:0;flex:1"><b>${esc(x.pr?.name||'Product')}</b><div class="muted small">Purchased ${x.i.qty} × ${money(x.i.cost)} · Already returned ${x.returned} · Remaining ${x.remaining}</div></div><input class="input purchaseReturnQty" data-product-id="${esc(x.i.productId)}" data-max="${x.remaining}" data-cost="${x.i.cost}" type="number" min="0" max="${x.remaining}" step="1" value="0" style="width:96px;text-align:center" oninput="document.getElementById('purchaseReturnTotal').textContent=money(calculatePurchaseReturnEstimate())"></div>`).join('')}</div>
   </div>
   <div class="card"><h2>Refund / Adjustment</h2>
    <div class="field" style="margin-top:12px"><label>REFUND METHOD</label><select class="input" id="purchaseReturnRefundMethod" onchange="state.purchaseReturnRefundMethod=this.value">${[['credit','Credit'],['cash','Cash'],['card','Card'],['bank_transfer','Bank Transfer'],['cheque','Cheque'],['other','Other']].map(([v,l])=>`<option value="${v}" ${state.purchaseReturnRefundMethod===v?'selected':''}>${l}</option>`).join('')}</select></div>
    <div class="field" style="margin-top:12px"><label>NOTES</label><textarea class="input" id="purchaseReturnNotes" rows="4" placeholder="Optional return note" oninput="state.purchaseReturnNotes=this.value">${esc(state.purchaseReturnNotes||'')}</textarea></div>
    <div class="card" style="margin-top:14px;background:var(--soft)"><div class="muted small">Estimated return value</div><div class="stat-value" id="purchaseReturnTotal">${money(0)}</div><div class="muted small" style="margin-top:4px">The final amount is calculated securely from the original purchase.</div></div>
    <div class="actions" style="margin-top:15px"><button class="btn" onclick="go('purchases')">Cancel</button><button class="btn primary" onclick="savePurchaseReturn('${p.id}')">Process Return</button></div>
   </div>
  </div></div>`;
 }
 const r=range('purchaseReturns'),list=data.purchaseReturns.filter(x=>dateInRange(x.date||'',r.from,r.to));
 return `<div class="page">${pageHead('Purchases','Purchase Returns','View processed purchase returns.','<button class="btn primary" onclick="go(\'purchases\')">← Purchases</button>')}${dateFilter('purchaseReturns','Return date')}<div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Return No.</th><th>Purchase</th><th>Date</th><th>Supplier</th><th>Refund</th><th>Status</th><th>Total</th></tr></thead><tbody>${list.map(x=>`<tr><td><b>${esc(x.number||'Return')}</b></td><td>${esc((data.purchases.find(p=>p.id===x.purchaseId)||{}).number||'—')}</td><td>${x.date?new Date(x.date).toLocaleString():'—'}</td><td>${esc(x.supplier||'Supplier')}</td><td>${esc(String(x.refundMethod||'').replace('_',' '))}</td><td><span class="pill good">${esc(x.status||'completed')}</span></td><td><b>${money(x.total)}</b></td></tr>`).join('')||'<tr><td colspan="7"><div class="empty">No purchase returns in this date range.</div></td></tr>'}</tbody></table></div></div></div>`;
}
function calculatePurchaseReturnEstimate(){let total=0;document.querySelectorAll('.purchaseReturnQty').forEach(el=>{const q=Math.max(0,Number(el.value||0));const max=Number(el.dataset.max||0);const cost=Number(el.dataset.cost||0);total+=Math.min(q,max)*cost});return total}
async function savePurchaseReturn(purchaseId){
 if(!account?.businessId){toast('Business is not ready');return}
 const items=[...document.querySelectorAll('.purchaseReturnQty')].map(el=>({product_id:el.dataset.productId,quantity:Math.min(Math.max(0,Number(el.value||0)),Number(el.dataset.max||0))})).filter(x=>x.quantity>0);
 if(!items.length){toast('Select at least one quantity to return');return}
 const refundMethod=state.purchaseReturnRefundMethod||document.getElementById('purchaseReturnRefundMethod')?.value||'credit';
 const notes=document.getElementById('purchaseReturnNotes')?.value||state.purchaseReturnNotes||null;
 const registerId=refundMethod==='cash'?(getOpenRegister()?.id||null):null;
 if(refundMethod==='cash'&&!registerId){toast('Open a cash register before processing a cash refund');return}
 const btn=[...document.querySelectorAll('button')].find(b=>b.textContent.trim()==='Process Return');if(btn){btn.disabled=true;btn.textContent='Processing...'}
 try{const {data:result,error}=await eazyApi.rpc('process_purchase_return',{p_business_id:account.businessId,p_purchase_id:purchaseId,p_items:items,p_refund_method:refundMethod,p_register_id:registerId,p_notes:notes});if(error){console.error('process_purchase_return RPC failed',error);toast(error.message||'Unable to process purchase return');return}const refreshed=await loadBusinessData();if(!refreshed)return;state.purchaseReturnPurchaseId=null;state.purchaseReturnRefundMethod='credit';state.purchaseReturnNotes='';toast(`Purchase return ${result?.reference_number||''} processed successfully`);go('purchaseReturns')}finally{if(btn){btn.disabled=false;btn.textContent='Process Return'}}
}
