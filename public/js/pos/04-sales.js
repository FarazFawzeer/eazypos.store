/* EazyPOS · Sales: list, invoice, edit/delete, sale returns, POS and register opening. */
function salesListView(){let q=state.salesQuery||'',list=data.sales.filter(s=>s.status!=='cancelled'&&(s.invoice+s.customer+s.payment).toLowerCase().includes(q.toLowerCase()));return `<div class="page">${pageHead('Sell','All Sales','Search, edit, delete or return sales.','<button class="btn primary" onclick="go(\'pos\');ensureRegister()">＋ Add Sale</button>')}<div class="card"><div class="searchbar"><input class="input" value="${esc(q)}" placeholder="Invoice / customer / payment" oninput="state.salesQuery=this.value;render()"><button class="btn" onclick="state.salesQuery='';render()">Clear</button></div><div class="table-wrap"><table class="table"><thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th><th>Actions</th></tr></thead><tbody>${list.map(s=>`<tr><td><b>${s.invoice}</b></td><td>${new Date(s.date).toLocaleString()}</td><td>${esc(s.customer)}</td><td>${s.payment}</td><td>${money(s.total)}</td><td><div class="actions"><button class="btn primary" onclick='viewInvoice(${JSON.stringify(s.id)})'>View Invoice</button><button class="btn" onclick='editSale(${JSON.stringify(s.id)})'>Edit</button><button class="btn blue" onclick='returnSale(${JSON.stringify(s.id)})'>Return</button><button class="btn danger" onclick='deleteSale(${JSON.stringify(s.id)})'>Delete</button></div></td></tr>`).join('')||`<tr><td colspan="6"><div class="empty">No sales found.</div></td></tr>`}</tbody></table></div></div></div>`}
function posListView(){return `<div class="page">${pageHead('Sell','List POS','Register sessions and POS activity.','<button class="btn primary" onclick="go(\'pos\');ensureRegister()">＋ Open POS</button>')}<div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Register</th><th>Opened</th><th>Closed</th><th>Status</th><th>Sales</th><th></th></tr></thead><tbody>${data.registers.slice().reverse().map(r=>{let c=registerCalc(r);return `<tr><td><b>REG-${r.id.toString().slice(-5)}</b></td><td>${new Date(r.openedAt).toLocaleString()}</td><td>${r.closedAt?new Date(r.closedAt).toLocaleString():'—'}</td><td><span class="pill ${r.status==='open'?'good':'warn'}">${r.status}</span></td><td>${money(c.totalSales)}</td><td><button class="btn" onclick='state.report=${JSON.stringify(String(r.id))};go("registerReport")'>Register Report</button></td></tr>`}).join('')||`<tr><td colspan="6"><div class="empty">No POS register sessions yet.</div></td></tr>`}</tbody></table></div></div></div>`}
function viewInvoice(id){state.invoiceSale=id;go('invoice')}
function printInvoice(){document.body.classList.add('printing-invoice');window.print();setTimeout(()=>document.body.classList.remove('printing-invoice'),800)}
function shareInvoice(){let s=data.sales.find(x=>x.id===state.invoiceSale);if(!s)return;let text=`${account.business}\nInvoice ${s.invoice}\n${new Date(s.date).toLocaleString()}\nCustomer: ${s.customer}\nTotal: ${money(s.total)}\nPayment: ${s.payment}`;shareText(text)}
function invoiceView(){let s=data.sales.find(x=>x.id===state.invoiceSale);if(!s)return `<div class="page">${pageHead('Sell','Invoice','Sale not found.','<button class="btn" onclick="go(\'sales\')">← Sales</button>')}<div class="card"><div class="empty">This sale could not be found.</div></div></div>`;let items=s.items||[];let subtotal=items.reduce((a,i)=>a+(Number(i.price||0)*Number(i.qty||0)),0),discount=Number(s.discount||0);let logo=account.logoUrl?`<img class="invoice-logo" src="${esc(account.logoUrl)}" alt="Business logo">`:'';return `<div class="page">${pageHead('Sell','Invoice','Invoice '+esc(s.invoice),'<button class="btn" onclick="go(\'sales\')">← Sales</button>')}<div class="actions invoice-actions"><button class="btn dark" onclick="printInvoice()">Print Invoice</button><button class="btn" onclick="shareInvoice()">Share</button><button class="btn primary" onclick="go(\'pos\');ensureRegister()">New Sale</button></div><div class="card invoice-page"><div class="invoice-head"><div class="invoice-brand">${logo}<div><h2 style="margin:0">${esc(account.business)}</h2>${account.address?`<div class="muted small">${esc(account.address)}</div>`:''}${account.phone?`<div class="muted small">${esc(account.phone)}</div>`:''}<div class="muted small">${esc(account.email||'')}</div></div></div><div class="invoice-meta"><b>${esc(s.invoice)}</b><span>${new Date(s.date).toLocaleString()}</span><span>Cashier: ${esc(account.owner||'Staff')}</span></div></div><div class="grid grid2" style="margin-top:20px"><div><div class="muted small">BILL TO</div><b>${esc(s.customer||'Walk-in Customer')}</b></div><div style="text-align:right"><div class="muted small">PAYMENT</div><b>${esc(s.payment||'Cash')}</b></div></div><table class="invoice-table"><thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead><tbody>${items.map(i=>`<tr><td>${esc(i.name||data.products.find(p=>p.id===i.productId)?.name||'Product')}</td><td>${i.qty}</td><td>${money(i.price)}</td><td>${money(Number(i.price||0)*Number(i.qty||0))}</td></tr>`).join('')}</tbody></table><div class="invoice-totals"><div class="invoice-total-row"><span>Subtotal</span><b>${money(subtotal)}</b></div><div class="invoice-total-row"><span>Discount</span><b>− ${money(discount)}</b></div><div class="invoice-total-row"><span>Tax</span><b>${money(0)}</b></div><div class="invoice-total-row grand"><span>Grand Total</span><b>${money(s.total)}</b></div><div class="invoice-total-row"><span>Amount Paid</span><b>${money(s.amountPaid??s.total)}</b></div><div class="invoice-total-row"><span>Balance</span><b>${money(s.amountDue??Math.max(0,Number(s.total||0)-Number(s.amountPaid??s.total)))}</b></div></div><div class="invoice-footer">${account.receiptFooter?esc(account.receiptFooter):'Thank you. Come Again!'}<br><span>Powered by EazyPOS</span></div></div></div>`}
function editSale(id){let s=data.sales.find(x=>x.id===id);if(!s)return;state.editingSale=id;state.customerId=s.customerId||null;state.creditPaid=Number(s.amountPaid||0);state.posDiscount=Number(s.discount||0);state.posDiscountType='amount';state.cart=s.items.map(i=>{let p=data.products.find(p=>p.id===i.productId);return {productId:i.productId,name:p?.name||'Product',price:i.price,qty:i.qty}});state.payment=s.payment==='Bank Transfer'?'Bank':s.payment;go('pos')}
async function deleteSale(id){
 let s=data.sales.find(x=>x.id===id);
 if(!s)return;
 if(!confirm('Delete this sale? Stock will be restored.'))return;
 if(!account?.businessId){toast('Business is not ready');return}
 const {data:result,error}=await eazyApi.rpc('delete_sale',{
   p_business_id:account.businessId,
   p_sale_id:id
 });
 if(error){console.error('delete_sale RPC failed',error);toast(error.message||'Unable to delete sale');return}
 const refreshed=await loadBusinessData();
 if(!refreshed)return;
 toast('Sale deleted and stock restored');
 render();
}
function returnSale(id){
 state.returnSaleId=id;
 state.returnRefundMethod='cash';
 state.returnNotes='';
 go('saleReturns');
}
function saleReturnView(){
 let s=data.sales.find(x=>x.id===state.returnSaleId);
 if(!s)return `<div class="page">${pageHead('Sell','Sale Return','Select a sale to return.','<button class="btn" onclick="go(\'sales\')">← Sales</button>')}<div class="card"><div class="empty">Select a sale from All Sales first.</div></div></div>`;
 const returnedByItem={};
 data.returns.filter(r=>r.saleId===s.id&&r.status==='completed').forEach(r=>(r.items||[]).forEach(i=>{
   const key=i.saleItemId||`${i.productId}`;
   returnedByItem[key]=(returnedByItem[key]||0)+Number(i.qty||0);
 }));
 const rows=s.items.map((i,idx)=>{
   const key=i.saleItemId||`${i.productId}`;
   const returned=Number(returnedByItem[key]||0);
   const remaining=Math.max(0,Number(i.qty||0)-returned);
   const p=data.products.find(p=>String(p.id)===String(i.productId));
   return {i,idx,p,returned,remaining};
 }).filter(x=>x.remaining>0);
 if(!rows.length)return `<div class="page">${pageHead('Sell','Sale Return','This sale has no remaining items available for return.','<button class="btn" onclick="go(\'sales\')">← Sales</button>')}<div class="card"><div class="empty">All quantities from this sale have already been returned.</div></div></div>`;
 return `<div class="page">${pageHead('Sell','Sale Return','Select the items and quantities to return.','<button class="btn" onclick="go(\'sales\')">← Sales</button>')}
 <div class="grid grid2">
  <div class="card">
   <div class="row"><div><h2>${esc(s.invoice)}</h2><div class="muted">${esc(s.customer)} · ${new Date(s.date).toLocaleString()}</div></div><span class="pill good">Original ${money(s.total)}</span></div>
   <div style="margin-top:16px">${rows.map(x=>`<div class="summary-row" style="gap:12px">
    <div style="min-width:0;flex:1"><b>${esc(x.p?.name||'Product')}</b><div class="muted small">Sold ${x.i.qty} × ${money(x.i.price)} · Already returned ${x.returned} · Remaining ${x.remaining}</div></div>
    <input class="input returnQty" data-product-id="${esc(x.i.productId)}" data-sale-item-id="${esc(x.i.saleItemId||'')}" data-max="${x.remaining}" type="number" min="0" max="${x.remaining}" step="1" value="0" style="width:96px;text-align:center" oninput="document.getElementById('returnTotal').textContent=money(calculateReturnEstimate())">
   </div>`).join('')}</div>
  </div>
  <div class="card">
   <h2>Refund</h2>
   <div class="field" style="margin-top:12px"><label>REFUND METHOD</label>
    <select class="input" id="returnRefundMethod" onchange="state.returnRefundMethod=this.value">
     ${[['cash','Cash'],['card','Card'],['bank_transfer','Bank Transfer'],['cheque','Cheque'],['credit','Credit'],['other','Other']].map(([v,l])=>`<option value="${v}" ${state.returnRefundMethod===v?'selected':''}>${l}</option>`).join('')}
    </select>
   </div>
   <div class="field" style="margin-top:12px"><label>NOTES</label><textarea class="input" id="returnNotes" rows="4" placeholder="Optional return note" oninput="state.returnNotes=this.value">${esc(state.returnNotes||'')}</textarea></div>
   <div class="card" style="margin-top:14px;background:var(--soft)"><div class="muted small">Estimated refund</div><div class="stat-value" id="returnTotal">${money(0)}</div><div class="muted small" style="margin-top:4px">The final amount is calculated securely from the original sale.</div></div>
   <div class="actions" style="margin-top:15px"><button class="btn primary" onclick="saveSaleReturn()">Process Return</button></div>
  </div>
 </div>
 </div>`;
}
function calculateReturnEstimate(){
 const s=data.sales.find(x=>x.id===state.returnSaleId);
 if(!s)return 0;
 const inputs=[...document.querySelectorAll('.returnQty')];
 let total=0;
 inputs.forEach(el=>{
   const productId=el.dataset.productId;
   const saleItem=s.items.find(i=>String(i.productId)===String(productId));
   const q=Math.max(0,Math.min(Number(el.value||0),Number(el.dataset.max||0)));
   if(saleItem)total+=q*Number(saleItem.price||0);
 });
 return total;
}
function saveSaleReturn(){
 const s=data.sales.find(x=>x.id===state.returnSaleId);
 if(!s||!account?.businessId){toast('Business is not ready');return}
 const inputs=[...document.querySelectorAll('.returnQty')];
 const items=inputs.map(el=>({
   product_id:el.dataset.productId,
   quantity:Math.max(0,Math.min(Number(el.value||0),Number(el.dataset.max||0)))
 })).filter(x=>x.quantity>0);
 if(!items.length){toast('Select at least one quantity to return');return}
 const refundMethod=document.getElementById('returnRefundMethod')?.value||state.returnRefundMethod||'cash';
 const notes=document.getElementById('returnNotes')?.value||state.returnNotes||null;
 const openRegister=getOpenRegister();
 const registerId=refundMethod==='cash'?(openRegister?.id||null):null;
 if(refundMethod==='cash'&&!registerId){toast('Open a cash register before processing a cash refund');return}
 const btn=[...document.querySelectorAll('button')].find(b=>b.textContent.trim()==='Process Return');
 if(btn){btn.disabled=true;btn.textContent='Processing...'}
 eazyApi.rpc('process_sale_return',{
   p_business_id:account.businessId,
   p_sale_id:s.id,
   p_items:items,
   p_refund_method:refundMethod,
   p_register_id:registerId,
   p_notes:notes
 }).then(async ({data:result,error})=>{
   if(error){console.error('process_sale_return RPC failed',error);toast(error.message||'Unable to process sale return');return}
   const refreshed=await loadBusinessData();
   if(!refreshed)return;
   state.returnSaleId=null;state.returnRefundMethod='cash';state.returnNotes='';
   toast(`Sale return ${result?.return_number||''} processed successfully`);
   go('saleReturns');
 }).finally(()=>{if(btn){btn.disabled=false;btn.textContent='Process Return'}})
}

function getPOSDiscountAmount(subtotal){const raw=String(state.posDiscount??'').trim();const hasPercent=raw.endsWith('%');let value=Number(hasPercent?raw.slice(0,-1):raw);if(!Number.isFinite(value)||value<0)value=0;if(hasPercent)state.posDiscountType='percent';return state.posDiscountType==='percent'?Math.min(100,value)*Math.max(0,subtotal)/100:Math.min(value,Math.max(0,subtotal))}
function updatePOSDiscount(value){const raw=String(value??'').trim();if(raw.endsWith('%')){state.posDiscountType='percent';state.posDiscount=raw.slice(0,-1).trim()}else{state.posDiscount=raw}}
function posView(){
 let open=getOpenRegister(),cart=state.cart||[],subtotal=cart.reduce((a,i)=>a+i.qty*i.price,0),shipping=0,discount=getPOSDiscountAmount(subtotal),total=Math.max(0,subtotal-discount);
 let q=state.posQuery||'',products=data.products.filter(p=>(p.name+p.sku+p.category).toLowerCase().includes(q.toLowerCase()));
 let selectedCustomer=data.customers.find(c=>String(c.id)===String(state.customerId));
 let customerLabel=selectedCustomer?selectedCustomer.name:'Walk-in Customer';
 const courier=false;
 const paymentOptions=['Cash','Card','Credit','Bank'];
 return `<div class="pos-shell"><div class="pos-toolbar"><button class="pos-tool" onclick="go('home')" title="Back" aria-label="Back">${icon('arrowLeft')}</button><div class="pos-location">${esc(account.business)}</div><div class="pos-date">${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</div><div class="pos-tools"><button class="pos-tool blue" onclick="openCalculator()" title="Calculator" aria-label="Calculator">${icon('calculator')}</button><button class="pos-tool primary" onclick="openRegisterReport()" title="Register Details" aria-label="Register details">${icon('register')}</button><button class="pos-tool danger" onclick="addExpense()" title="Add Expense" aria-label="Add expense">${icon('wallet')}</button><button class="pos-tool" onclick="togglePOSFullscreen()" title="Full Screen" aria-label="Full screen">${icon('maximize')}</button><button class="pos-tool danger" onclick="closeRegister()" title="Close Register" aria-label="Close register">${icon('power')}</button></div></div><div class="pos-layout pos-full"><div class="pos-workspace"><div class="card pos-products-card"><div class="pos-search-row"><div class="searchbar" style="margin:0"><input class="input" value="${esc(q)}" placeholder="Enter product name / SKU / scan barcode" oninput="state.posQuery=this.value;renderPosProductFilter(this.value)"><button class="btn" onclick="state.posQuery='';renderPosProductFilter('')">Clear</button></div><button class="btn primary" onclick="openRegisterReport()">${icon('register')} Register</button></div><div class="pos-filter-row"><div class="tabs" style="margin-top:12px"><button class="tab active" onclick="state.posQuery='';renderPosProductFilter('')">All</button>${data.categories.slice(0,8).map(c=>`<button class="tab" onclick="state.posQuery=${esc(JSON.stringify(c))};render()">${esc(c)}</button>`).join('')}</div><button class="pos-view-toggle" onclick="state.posCompact=!posCompact();render()" aria-pressed="${posCompact()?'false':'true'}" title="${posCompact()?'Show price, stock and SKU':'Show more products'}">${icon(posCompact()?'rows':'grid')}<span>${posCompact()?'Details':'Compact'}</span></button></div><div class="pos-products${posCompact()?' compact':''}" id="posProductGrid">${products.map(p=>`<button class="product-tile" data-pos-search="${esc((p.name+' '+p.sku+' '+p.category).toLowerCase())}" onclick='addToCart(${JSON.stringify(p.id)})'>${productThumb(p,'tile')}<b>${esc(p.name)}</b><div class="price">${money(p.sell)}</div><div class="stock">${p.stock} ${esc(p.unit)} in stock</div><div class="small" style="margin-top:8px;color:var(--muted)">${esc(p.sku)}</div></button>`).join('')||'<div class="empty">No products found.</div>'}</div></div></div><div class="card cart pos-screen-cart"><div class="row"><div><h2>Current Sale</h2><button class="customer-pick" style="margin-top:4px" onclick="openPOSCustomerPicker()">${icon('user')} ${esc(customerLabel)} ${icon('chevronDown','i-sm')}</button>${selectedCustomer?`<div class="muted small" style="margin-top:5px">${esc(selectedCustomer.phone||'')} ${selectedCustomer.credit?`· Due ${money(selectedCustomer.credit)}`:''}</div>`:''}</div><button class="btn" onclick="clearPOSCart()">Clear</button></div><div class="cart-items">${cart.map((i,idx)=>`<div class="cart-row"><div><b>${esc(i.name)}</b><div class="muted small">${money(i.price)} each</div></div><div class="qty"><button onclick="cartQty(${idx},-1)">−</button><span>${i.qty}</span><button onclick="cartQty(${idx},1)">+</button></div><b>${money(i.qty*i.price)}</b></div>`).join('')||'<div class="empty" style="margin-top:12px">Add products to begin.</div>'}</div><div class="summary-row"><span>Items</span><b>${cart.reduce((a,i)=>a+i.qty,0)}</b></div><div class="summary-row"><span>Subtotal</span><b>${money(subtotal)}</b></div><div class="field" style="margin:10px 0"><label>DISCOUNT</label><div class="tabs" style="margin:0 0 8px"><button class="tab ${state.posDiscountType!=='percent'?'active':''}" onclick="state.posDiscountType='amount';render()">Amount (Rs.)</button><button class="tab ${state.posDiscountType==='percent'?'active':''}" onclick="state.posDiscountType='percent';render()">Percentage (%)</button></div><input class="input" type="text" inputmode="decimal" value="${esc(state.posDiscount??0)}${state.posDiscountType==='percent'?'%':''}" placeholder="${state.posDiscountType==='percent'?'Enter discount percentage':'Enter discount amount'}" onchange="updatePOSDiscount(this.value);render()"><div class="muted small" style="margin-top:5px">${state.posDiscountType==='percent'?'Percentage discount':'Fixed amount discount'} · Discount: ${money(discount)}</div></div><div class="summary-row"><span>Discount</span><b>− ${money(discount)}</b></div><div class="pay-methods">${paymentOptions.map(m=>`<button class="pay ${state.payment===m?'active':''}" onclick="setPOSPayment('${m}')">${m}</button>`).join('')}</div>${state.payment==='Credit'?`<div class="field" style="margin-top:12px"><label>AMOUNT PAID NOW</label><input class="input" id="creditPaid" type="number" min="0" max="${total.toFixed(2)}" step="0.01" value="${Math.min(Number(state.creditPaid||0),total).toFixed(2)}" oninput="state.creditPaid=Math.max(0,Math.min(Number(this.value||0),${total}));updateCreditSummary()"><div class="muted small" style="margin-top:5px">Amount due after this sale: <b id="creditDue">${money(Math.max(0,total-Number(state.creditPaid||0)))}</b></div></div>`:''}<div class="total-line"><span>${state.payment==='COD'||state.payment==='Credit'?'Sale Total':'Payable'}</span><strong id="posPayableTotal">${money(total)}</strong></div><div class="pos-bottom-actions"><button class="btn dark" onclick="completeSale()">${state.editingSale?'UPDATE SALE':'SAVE ('+String(state.payment||'Cash').toUpperCase()+')'} →</button><button class="btn danger" onclick="clearPOSCart()">Cancel</button></div></div></div></div>`}
function clearPOSCart(){state.cart=[];state.customerId=null;state.editingSale=null;state.creditPaid=0;state.posDiscount=0;state.posDiscountType='amount';state.orderType='store';state.payment='Cash';state.courierShippingCost=0;state.courierShippingDate='';state.courierExpectedDate='';state.courierCarrier='';state.courierTracking='';state.courierAddress='';state.courierNotes='';render()}
function setPOSOrderType(type){state.orderType='store';if(state.payment==='COD')state.payment='Cash';render()}
function renderPOSShippingTotal(){const el=document.getElementById('posPayableTotal');if(el){const subtotal=(state.cart||[]).reduce((a,i)=>a+i.qty*i.price,0);el.textContent=money(subtotal+(state.orderType==='courier'?Number(state.courierShippingCost||0):0));}}
function openPOSCustomerPicker(){
 let list=data.customers.slice().sort((a,b)=>a.name.localeCompare(b.name));
 modal(`<div class="modal-head"><div><div class="eyebrow">POS</div><h2>Select Customer</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="searchbar"><div class="search-wrap" style="flex:1"><input class="input" id="posCustomerSearch" placeholder="Search name or phone" oninput="filterPOSCustomers(this.value);renderPOSCustomerSuggestions()"><div id="posCustomerSuggest" class="search-suggest"></div></div><button class="btn" onclick="state.customerId=null;state.orderType='store';closeModal();render()">Walk-in</button></div><div id="posCustomerList" style="margin-top:12px;max-height:360px;overflow:auto">${list.map(c=>`<button class="customer-option" data-search="${esc((c.name+' '+c.phone).toLowerCase())}" onclick="selectPOSCustomer('${c.id}')"><div><b>${esc(c.name)}</b><div class="muted small">${esc(c.phone||'No phone')} · ${({regular:'Regular',walk_in:'Walk-in',credit:'Credit'}[c.customerType]||'Regular')} ${c.credit?`· Due ${money(c.credit)}`:''}</div></div><span>›</span></button>`).join('')||'<div class="empty">No customers yet.</div>'}</div><button class="btn primary" style="width:100%;margin-top:12px" onclick="closeModal();addCustomer()">＋ Add New Customer</button>`)
}
function filterPOSCustomers(q){let s=(q||'').toLowerCase().trim();document.querySelectorAll('#posCustomerList .customer-option').forEach(el=>el.style.display=(!s||(el.dataset.search||'').includes(s))?'flex':'none')}
function selectPOSCustomer(id){state.customerId=id;closeModal();render()}
function setPOSPayment(method){state.payment=method;if(method==='Credit' && !Number.isFinite(Number(state.creditPaid)))state.creditPaid=0;if(method!=='Credit')state.creditPaid=0;render()}
function updateCreditSummary(){const el=document.getElementById('creditDue');if(!el)return;const subtotal=(state.cart||[]).reduce((a,i)=>a+i.qty*i.price,0);const total=Math.max(0,subtotal-getPOSDiscountAmount(subtotal));const paid=Math.max(0,Math.min(Number(state.creditPaid||0),total));el.textContent=money(Math.max(0,total-paid))}

function renderPosProductFilter(query){
  const q=(query||'').toLowerCase().trim();
  document.querySelectorAll('#posProductGrid .product-tile').forEach(tile=>{
    const hay=tile.dataset.posSearch||'';
    tile.style.display=!q||hay.includes(q)?'':'none';
  });
  const grid=document.getElementById('posProductGrid');
  if(grid){
    let empty=grid.querySelector('.pos-no-results');
    const visible=[...grid.querySelectorAll('.product-tile')].some(x=>x.style.display!=='none');
    if(!visible){
      if(!empty){ empty=document.createElement('div'); empty.className='empty pos-no-results'; empty.textContent='No products found.'; grid.appendChild(empty); }
    }else if(empty){ empty.remove(); }
  }
}
function addToCart(id){let p=data.products.find(x=>x.id===id);if(!p)return;let r=state.cart.find(x=>x.productId===id);if(r){if(r.qty<p.stock)r.qty++;else toast('Not enough stock')}else state.cart.push({productId:id,name:p.name,price:p.sell,qty:1});render()}
function cartQty(i,d){let r=state.cart[i],p=data.products.find(x=>x.id===r.productId);r.qty+=d;if(r.qty<=0)state.cart.splice(i,1);if(p&&r.qty>p.stock)r.qty=p.stock;render()}
async function completeSale(){
 let open=getOpenRegister();
 if(!open){openRegister();return}
 if(!state.cart.length){toast('Add at least one product');return}
 const courier=false;
 let subtotal=state.cart.reduce((a,i)=>a+i.qty*i.price,0),shipping=0,discount=getPOSDiscountAmount(subtotal),total=Math.max(0,subtotal-discount);
 if(state.editingSale){
   const editingId=state.editingSale;
   if(String(state.payment||'Cash').toLowerCase()==='credit'&&!state.customerId){toast('Select a customer for a credit sale');return}
   if(String(state.payment||'Cash')==='COD'){state.payment='Cash'}
   const paidNow=String(state.payment||'Cash').toLowerCase()==='credit'?Math.max(0,Math.min(Number(state.creditPaid||0),total)):total;
   const paymentMap={Cash:'cash',Card:'card',Credit:'credit',Bank:'bank_transfer',Cheque:'cheque',Other:'other'};
   const selectedPayment=String(state.payment||'Cash');
   const items=state.cart.map(i=>({product_id:i.productId,quantity:i.qty,unit_price:i.price,discount:0,tax:0}));
   const payments=[{payment_method:paymentMap[selectedPayment]||'other',amount:paidNow,reference_number:null,notes:selectedPayment==='Credit'?'Customer credit sale':null}];
   const {data:result,error}=await eazyApi.rpc('update_sale',{p_business_id:account.businessId,p_sale_id:editingId,p_customer_id:state.customerId||null,p_items:items,p_payments:payments,p_discount:discount,p_tax:0,p_shipping_charge:shipping,p_other_charge:0,p_notes:courier?'Courier order updated':null});
   if(error){console.error('update_sale RPC failed',error);toast(error.message||'Unable to update sale');return}
   state.cart=[];state.customerId=null;state.editingSale=null;state.creditPaid=0;state.posDiscount=0;clearPOSCourierState();
   const refreshed=await loadBusinessData();if(!refreshed)return;toast('Sale updated and saved');go('sales');return;
 }
 for(const i of state.cart){let p=data.products.find(x=>x.id===i.productId);if(!p||p.stock<i.qty){toast('Insufficient stock for '+i.name);return}}
 /* The server checks the signed-in session for every sale. */
 const items=state.cart.map(i=>({product_id:i.productId,quantity:i.qty,unit_price:i.price,discount:0,tax:0}));
 const selectedPayment=String(state.payment||'Cash');
 if(selectedPayment==='Credit'&&!state.customerId){toast('Select a customer for a credit sale');return}
 if(selectedPayment==='COD'){state.payment='Cash';toast('Online/COD sales are temporarily disabled. Please use a regular sale.');return}
 const paidNow=selectedPayment==='Credit'?Math.max(0,Math.min(Number(state.creditPaid||0),total)):total;
 const paymentMap={Cash:'cash',Card:'card',Credit:'credit',Bank:'bank_transfer',Cheque:'cheque',Other:'other'};
 const payments=[{payment_method:paymentMap[selectedPayment]||'other',amount:paidNow,reference_number:null,notes:selectedPayment==='Credit'?'Customer credit sale':null}];
 const {data:result,error}=await eazyApi.rpc('complete_sale',{p_business_id:account.businessId,p_customer_id:state.customerId||null,p_register_id:open.id,p_items:items,p_payments:payments,p_discount:discount,p_tax:0,p_shipping_charge:shipping,p_other_charge:0,p_notes:courier?'Courier order'+(isCOD?' · COD':''):null});
 if(error){console.error('complete_sale RPC failed',error);toast(error.message||'Unable to complete sale');return}
 state.cart=[];state.customerId=null;state.editingSale=null;state.creditPaid=0;state.posDiscount=0;clearPOSCourierState();
 const refreshed=await loadBusinessData();if(!refreshed)return;go('sales');
}
function clearPOSCourierState(){state.orderType='store';state.payment='Cash';state.courierShippingCost=0;state.courierShippingDate='';state.courierExpectedDate='';state.courierCarrier='';state.courierTracking='';state.courierAddress='';state.courierNotes=''}
function getOpenRegister(){return data.registers.find(r=>r.status==='open')||null}
function ensureRegister(){if(!getOpenRegister())openRegister()}
function openCalculator(){modal(`<div class="modal-head"><div><div class="eyebrow">POS Tool</div><h2>Calculator</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="calculator-display" id="calcDisplay">0</div><div class="calc-grid">${['7','8','9','÷','4','5','6','×','1','2','3','−','0','.','C','+'].map(k=>`<button class="${['÷','×','−','+'].includes(k)?'op':''}" onclick="calcPress('${k}')">${k}</button>`).join('')}</div><button class="btn primary" style="width:100%;margin-top:10px" onclick="closeModal()">Done</button>`)}
function calcPress(k){let d=document.getElementById('calcDisplay');if(!d)return;if(k==='C'){d.textContent='0';return}if(k==='×'||k==='÷'||k==='−'||k==='+'){d.textContent=(d.textContent==='0'?'':d.textContent)+' '+k+' ';return}d.textContent=d.textContent==='0'?k:d.textContent+k}
function togglePOSFullscreen(){if(!document.fullscreenElement){document.documentElement.requestFullscreen?.()}else{document.exitFullscreen?.()}}
function openRegister(){modal(`<div class="modal-head"><div><div class="eyebrow">Cash Register</div><h2>Open Register</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="muted small" style="margin-bottom:14px">Enter the cash currently in the drawer before starting sales.</div><div class="field"><label>OPENING CASH</label><input class="input" id="openingCash" type="number" min="0" value="10000"></div><div class="actions" style="margin-top:15px"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn primary" onclick="confirmOpenRegister()">Open Register</button></div>`)}
async function confirmOpenRegister(){
 let amount=Number(document.getElementById('openingCash').value||0);
 if(!account?.businessId){toast('Business is not ready');return}
 if(!Number.isFinite(amount)||amount<0){toast('Enter a valid opening cash amount');return}
 const {data:row,error}=await eazyApi.post('/api/registers',{opening_cash:amount});
 if(error){apiError('Unable to open register',error);return}
 await loadBusinessData();
 closeModal();toast('Register opened');go('pos');
}

/* Compact POS grid (photo + name) is the default; Details shows price, stock and SKU. */
function posCompact(){return state.posCompact!==false}
