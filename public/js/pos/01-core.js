/* EazyPOS · State, data loading, helpers, navigation, routing and the home dashboard. */
const KEY='eazyposDataV3';
let account=null;
const DEFAULT_UNITS=['Pieces','Kilograms','Litres'];
const currencySymbols={LKR:'Rs. ',USD:'$ ',GBP:'£ ',EUR:'€ '};
let data={products:[],sales:[],purchases:[],returns:[],purchaseReturns:[],expenses:[],customers:[],suppliers:[],users:[],invitations:[],stockHistory:[],quotations:[],shipments:[],registers:[],categories:[],brands:[],units:[],priceGroups:[],warranties:[],settings:{business:'',currency:'USD',tax:0,receiptFooter:'Thank you!'},expenseCategories:[]};
function save(){/* All business data is saved on the server through eazyApi; browser storage is never used as the database. */}
function apiError(context,error){console.error(context,error);toast((error&&error.message)||context);}
async function loadBusinessData(){
 const {data:payload,error}=await eazyApi.get('/api/bootstrap');
 if(error||!payload){if(error)console.error('Unable to load business data',error);return false;}
 account=payload.account;
 data.settings.business=account.business;data.settings.currency=account.currency||'LKR';data.settings.tax=Number(account.taxRate||0);data.settings.receiptFooter=account.receiptFooter||'Thank you!';
 const {products,categories,brands,units,customers,suppliers,sales,sale_items:saleItems,sale_payments:payments,purchases,purchase_items:purchaseItems,purchase_returns:purchaseReturns,purchase_return_items:purchaseReturnItems,expenses,expense_categories:expenseCategories,cash_registers:registers,sale_returns:saleReturns,sale_return_items:saleReturnItems,business_users:businessUsers,staff_invitations:staffInvitations,shipments:shipmentRows,stock_movements:stockMovements}=payload;
 const catMap=Object.fromEntries(categories.map(x=>[x.id,x.name]));const brandMap=Object.fromEntries(brands.map(x=>[x.id,x.name]));const unitMap=Object.fromEntries(units.map(x=>[x.id,x.name]));const custMap=Object.fromEntries(customers.map(x=>[x.id,x.name]));const supplierMap=Object.fromEntries(suppliers.map(x=>[x.id,x.name]));
 data.products=products.map(p=>({id:p.id,name:p.name,sku:p.sku||p.barcode||'',barcode:p.barcode||'',category:catMap[p.category_id]||'',brand:brandMap[p.brand_id]||'',cost:Number(p.cost_price||0),sell:Number(p.selling_price||0),stock:Number(p.stock_quantity||0),low:Number(p.low_stock_alert||0),unit:unitMap[p.unit_id]||'pcs',createdAt:p.created_at,image:p.image_url||''}));
 const allProductNames=Object.fromEntries([...products,...(payload.archived_products||[])].map(x=>[x.id,x.name]));
 data.categories=categories.map(x=>x.name);data.brands=brands.map(x=>x.name);data.units=units.map(x=>x.name);data.expenseCategories=expenseCategories.map(x=>({id:x.id,name:x.name}));data.customers=customers.map(c=>({id:c.id,name:c.name,phone:c.phone||'',email:c.email||'',address:c.address||'',notes:c.notes||'',customerType:c.customer_type||'regular',creditLimit:Number(c.credit_limit||0),credit:Number(c.credit_balance||0),createdAt:c.created_at}));data.suppliers=suppliers.map(x=>({id:x.id,name:x.name,contactPerson:x.contact_person||'',phone:x.phone||'',email:x.email||'',address:x.address||'',notes:x.notes||'',balance:Number(x.current_balance||0),openingBalance:Number(x.opening_balance||0),createdAt:x.created_at}));
 const itemsBySale={};saleItems.forEach(i=>(itemsBySale[i.sale_id]??=[]).push({productId:i.product_id,name:allProductNames[i.product_id]||'',qty:Number(i.quantity||0),price:Number(i.unit_price||0),cost:Number(i.cost_price||0),discount:Number(i.discount||0),tax:Number(i.tax||0)}));
 const payBySale={};payments.forEach(p=>{(payBySale[p.sale_id]??=[]).push(p)});
 data.sales=sales.map(s=>{const ps=payBySale[s.id]||[];const payment=ps.length?ps[0].payment_method:(s.payment_status==='credit'?'Credit':'Other');return {id:s.id,registerId:s.register_id||null,invoice:s.invoice_number,date:s.sale_date,customer:custMap[s.customer_id]||'Walk-in Customer',customerId:s.customer_id||null,payment:payment.replace('_',' ').replace(/\b\w/g,m=>m.toUpperCase()),payments:ps.map(p=>({method:(p.payment_method||'other').replace('_',' ').replace(/\b\w/g,m=>m.toUpperCase()),amount:Number(p.amount||0)})),total:Number(s.grand_total||0),discount:Number(s.discount||0),amountPaid:Number(s.amount_paid||0),amountDue:Number(s.amount_due||0),paymentStatus:s.payment_status,status:s.status||'completed',items:itemsBySale[s.id]||[]};});
 data.returns=saleReturns.map(r=>({id:r.id,registerId:r.register_id||null,saleId:r.sale_id,number:r.return_number,invoice:(sales.find(s=>s.id===r.sale_id)||{}).invoice_number||'',date:r.return_date||r.created_at,customer:custMap[r.customer_id]||'Walk-in Customer',refundMethod:(r.refund_method||'cash').replace('_',' ').replace(/\b\w/g,m=>m.toUpperCase()),status:r.status||'completed',total:Number(r.grand_total||0),notes:r.notes||'',items:saleReturnItems.filter(i=>i.sale_return_id===r.id).map(i=>({saleItemId:i.sale_item_id,productId:i.product_id,qty:Number(i.quantity||0),price:Number(i.unit_price||0),discount:Number(i.discount||0),tax:Number(i.tax||0),lineTotal:Number(i.line_total||0)}))}));
 const itemsByPurchase={};purchaseItems.forEach(i=>(itemsByPurchase[i.purchase_id]??=[]).push({id:i.id,productId:i.product_id,qty:Number(i.quantity||0),cost:Number(i.unit_cost||0),sellingPrice:Number(i.selling_price||0),discount:Number(i.discount||0),tax:Number(i.tax||0),lineTotal:Number(i.line_total||0)}));
 data.purchases=purchases.map(p=>({id:p.id,number:p.reference_number||p.id,date:p.purchase_date||p.created_at,supplier:supplierMap[p.supplier_id]||'Supplier',supplierId:p.supplier_id||null,payment:(p.payment_method||'cash').replace('_',' ').replace(/\b\w/g,m=>m.toUpperCase()),total:Number(p.grand_total||0),amountPaid:Number(p.amount_paid||0),amountDue:Number(p.amount_due||0),status:p.status||'completed',notes:p.notes||'',items:itemsByPurchase[p.id]||[]}));
 const purchaseReturnItemsByReturn={}; purchaseReturnItems.forEach(i=>(purchaseReturnItemsByReturn[i.purchase_return_id]??=[]).push({productId:i.product_id,qty:Number(i.quantity||0),cost:Number(i.unit_cost||0),discount:Number(i.discount||0),tax:Number(i.tax||0),lineTotal:Number(i.line_total||0)}));
 data.purchaseReturns=purchaseReturns.map(r=>({id:r.id,purchaseId:r.purchase_id||null,number:r.reference_number,date:r.return_date||r.created_at,supplier:supplierMap[r.supplier_id]||'Supplier',supplierId:r.supplier_id||null,refundMethod:r.refund_method||'credit',status:r.status||'completed',total:Number(r.grand_total||0),notes:r.notes||'',items:purchaseReturnItemsByReturn[r.id]||[]}));
 const expenseCatMap=Object.fromEntries(expenseCategories.map(x=>[x.id,x.name]));data.expenses=expenses.map(e=>({id:e.id,name:e.description||'Expense',category:expenseCatMap[e.category_id]||'Other',categoryId:e.category_id||null,amount:Number(e.amount||0),date:e.expense_date||e.created_at,registerId:e.register_id||null,payment:(e.payment_method||'cash').replace('_',' ').replace(/\b\w/g,m=>m.toUpperCase()),note:e.notes||''}));
 data.registers=registers.map(r=>({id:r.id,openedAt:r.opened_at,closedAt:r.closed_at,openingCash:Number(r.opening_cash||0),status:r.status,actualClosing:r.actual_closing_cash}));
 data.users=businessUsers.map(u=>({id:u.id,userId:u.user_id,name:u.full_name||'Staff User',username:u.username||'',role:(u.role||'cashier').charAt(0).toUpperCase()+(u.role||'cashier').slice(1),status:(u.status||'active').charAt(0).toUpperCase()+(u.status||'active').slice(1),permissions:u.permissions||{}}));
 data.invitations=staffInvitations.map(i=>({id:i.id,email:i.email,role:(i.role||'cashier').charAt(0).toUpperCase()+(i.role||'cashier').slice(1),status:(i.status||'pending').charAt(0).toUpperCase()+(i.status||'pending').slice(1),expiresAt:i.expires_at,createdAt:i.created_at}));
 data.shipments=(shipmentRows||[]).map(s=>({
   id:s.id,
   number:s.shipment_number,
   businessId:s.business_id,
   saleId:s.sale_id||null,
   customerId:s.customer_id||null,
   customer:custMap[s.customer_id]||'Walk-in Customer',
   date:s.shipping_date||s.created_at,
   shippingDate:s.shipping_date||null,
   expectedDeliveryDate:s.expected_delivery_date||null,
   deliveredAt:s.delivered_at||null,
   shippingAddress:s.shipping_address||'',
   carrier:s.carrier||'',
   trackingNumber:s.tracking_number||'',
   shippingCost:Number(s.shipping_cost||0),
   status:s.status||'pending',
   notes:s.notes||'',
   createdBy:s.created_by||null,
   createdAt:s.created_at,
   updatedAt:s.updated_at
 }));
 const productNames=Object.fromEntries(products.map(p=>[p.id,p.name]));
 data.quotations=(payload.quotations||[]).map(q=>({id:q.id,number:q.quotation_number,customer:q.customer_name||custMap[q.customer_id]||'Walk-in Customer',date:String(q.quotation_date||q.created_at||'').slice(0,10),total:Number(q.grand_total||0),status:q.status||'draft'}));
 data.stockHistory=(stockMovements||[]).map(m=>{const q=Number(m.quantity||0);return {product:productNames[m.product_id]||'Product',reason:m.note||String(m.movement_type||'').replace(/_/g,' '),date:m.created_at,change:(q>0?'+':'')+q}});
 return true;
}

function sym(){return currencySymbols[data.settings.currency]||'Rs. '}
function money(n){return sym()+Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function today(){return new Date().toISOString().slice(0,10)}
function now(){return new Date().toLocaleString()}
let state={view:(location.hash.slice(1).split('/')[0]||'home'),productMenu:false,purchaseMenu:false,sellMenu:false,contactMenu:false,selectedProduct:null,editingSale:null,invoiceSale:null,cart:[],payment:'Cash',creditPaid:0,posDiscount:0,posDiscountType:'amount',customerId:null,orderType:'store',courierShippingCost:0,courierShippingDate:'',courierExpectedDate:'',courierCarrier:'',courierTracking:'',courierAddress:'',courierNotes:'',register:null,report:null,purchaseCart:[],purchasePayment:'Cash',purchaseSupplier:'',purchasePaid:'',purchaseDiscount:0,purchaseTax:0,purchaseShipping:0,purchaseOther:0,purchaseDate:today(),purchaseSupplierSearch:'',editingPurchaseId:null,pendingExpense:null};
function isMobile(){return innerWidth<=820}
function permissionKeyForView(view){
 const v=String(view||'');
 if(['home','sell','pos','sales','addSale','listPOS','quotations','saleReturns','importSales'].includes(v)) return 'sales';
 if(v==='shipments') return 'shipments';
 if(['purchases','addPurchase','purchaseReturns'].includes(v)) return 'purchases';
 if(['products','addProduct','updatePrice','labels','variations','importProducts','openingStock','priceGroups','units','categories','brands','warranties'].includes(v)) return 'products';
 if(v==='customers') return 'sales';
 if(v==='suppliers') return 'purchases';
 if(v==='stock') return 'stock';
 if(v==='expenses') return 'expenses';
 if(['reports','profit','salesReport','purchaseReport','stockReport','registerReport','expenseReport','supplierReport'].includes(v)) return 'reports';
 if(v==='users') return 'users';
 if(['paymentAccounts','notifications','settings'].includes(v)) return 'settings';
 return null;
}
function hasPageAccess(view){
 if(!account) return false;
 const role=String(account.role||'').toLowerCase();
 if(role==='owner') return true;
 const key=permissionKeyForView(view);
 if(!key) return true;
 return !!account.permissions?.[key];
}
function accessDeniedView(view){
 const labels={sales:'Sales & POS',purchases:'Purchases',products:'Products',reports:'Reports',expenses:'Expenses',stock:'Stock Adjustment',users:'User Management',shipments:'Shipments',settings:'Settings'};
 const label=labels[permissionKeyForView(view)]||'this section';
 return `<div class="page"><div class="card" style="max-width:720px;margin:60px auto;text-align:center;padding:52px 28px"><div style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin:0 auto 18px;background:#fff0ee;color:#d84a3a;font-size:30px">!</div><div class="eyebrow">ACCESS RESTRICTED</div><h1 style="margin:8px 0 10px">You don't have access</h1><p class="muted" style="max-width:520px;margin:0 auto;line-height:1.6">Your current role does not have permission to access ${esc(label)}. Ask your business owner or administrator to enable this permission for your account.</p><button class="btn primary" style="margin-top:22px" onclick="go('home')">Back to Home</button></div></div>`;
}
function navSections(){return [
 {id:'home',icon:icon('home'),label:'Home'},
 {id:'users',icon:icon('users'),label:'User Management'},
 {id:'contacts',icon:icon('contacts'),label:'Contacts',expand:'contactMenu',items:[['customers','Customers'],['suppliers','Suppliers']]},
 {id:'products',icon:icon('package'),label:'Products',expand:'productMenu',items:[['products','List Products'],['addProduct','Add Product'],['updatePrice','Update Price'],['labels','Print Labels'],['variations','Variations'],['importProducts','Import Products'],['openingStock','Import Opening Stock'],['priceGroups','Selling Price Group'],['units','Units'],['categories','Categories'],['brands','Brands'],['warranties','Warranties']]},
 {id:'purchases',icon:icon('truck'),label:'Purchases',expand:'purchaseMenu',items:[['purchases','List Purchases'],['addPurchase','Add Purchase'],['purchaseReturns','List Purchase Return']]},
 {id:'sell',icon:icon('cart'),label:'Sell',expand:'sellMenu',items:[['sales','All Sales'],['addSale','Add Sale'],['listPOS','List POS'],['pos','POS'],['quotations','Quotations'],['saleReturns','List Sell Return'],['shipments','Shipments'],['importSales','Import Sales']]},
 {id:'stock',icon:icon('sliders'),label:'Stock Adjustment'},
 {id:'expenses',icon:icon('wallet'),label:'Expenses'},
 {id:'paymentAccounts',icon:icon('card'),label:'Payment Accounts'},
 {id:'reports',icon:icon('chart'),label:'Reports'},
 {id:'notifications',icon:icon('mail'),label:'Notification Templates'},
 {id:'settings',icon:icon('settings'),label:'Settings'}];}
function buildNav(){
 let html='<div class="side-top"><img class="side-logo" src="assets/eazypos-logo-green.png" alt="EazyPOS"><div class="business-switch"><b>'+esc(account.business)+'</b><span>'+esc(account.owner||account.username)+'</span></div></div><div class="nav">';
 navSections().forEach(function(sec){
  const sectionAccess=hasPageAccess(sec.id);
  const visibleItems=(sec.items||[]).filter(function(item){return hasPageAccess(item[0]);});
  if(!sectionAccess && !visibleItems.length) return;
  let active=state.view===sec.id||visibleItems.some(function(x){return x[0]===state.view});
  html+='<button class="nav-btn '+(active?'active':'')+'" onclick="navClick(\''+sec.id+'\',\''+(sec.expand||'')+'\')"><span class="nav-ico">'+sec.icon+'</span><span>'+sec.label+'</span>'+(visibleItems.length?'<span class="nav-chevron'+(state[sec.expand]?' open':'')+'" aria-hidden="true">'+(state[sec.expand]?'⌃':'⌄')+'</span>':'')+'</button>';
  if(visibleItems.length&&state[sec.expand]){
   html+='<div class="submenu">';
   visibleItems.forEach(function(item){html+='<button class="sub-btn '+(state.view===item[0]?'active':'')+'" onclick="event.stopPropagation();go(\''+item[0]+'\')">'+item[1]+'</button>';});
   html+='</div>';
  }
 });
 html+='</div><div class="sidebar-bottom"><button class="nav-btn" onclick="logout()"><span class="nav-ico">'+icon('logout')+'</span>Log out</button></div>';
 document.getElementById('desktopSidebar').innerHTML=html;
 document.getElementById('drawerInner').innerHTML=html;
}
function navClick(id,expand){if(expand){state[expand]=!state[expand];renderNav()}else go(id)}
function renderNav(){buildNav();document.getElementById('mobileBottom').innerHTML=[['home',icon('home'),'Home'],['sales',icon('receipt'),'Sales'],['pos',icon('cart'),'POS'],['products',icon('package'),'Products'],['drawer',icon('menu'),'Menu']].map(x=>`<button class="${x[0]==='pos'?'nav-pos ':''}${state.view===x[0]?'active':''}" onclick="${x[0]==='drawer'?'toggleDrawer()':`go('${x[0]}')`}" ${state.view===x[0]?'aria-current="page"':''}>${x[0]==='pos'?`<span class="nav-pos-disc">${x[1]}</span>`:x[1]}<span>${x[2]}</span></button>`).join('')}
function toggleDrawer(){document.getElementById('drawer').classList.toggle('open')}
function syncRouteFromHash(shouldRender=false){
 const parts=decodeURIComponent((location.hash||'').replace(/^#/,'')).split('/');
 const route=parts[0]||'home';
 state.view=route;
 if(route==='invoice')state.invoiceSale=parts[1]||state.invoiceSale||null;
 if(route==='saleReturns')state.returnSaleId=parts[1]||null;
 else state.returnSaleId=null;
 if(shouldRender&&typeof render==='function'){render();if(isMobile())document.getElementById('drawer')?.classList.remove('open')}
}
syncRouteFromHash(false);
window.addEventListener('popstate',()=>syncRouteFromHash(true));
function go(view){
 state.view=view;
 if(view!=='saleReturns')state.returnSaleId=null;
 let route=view;
 if(view==='invoice'&&state.invoiceSale)route+='/'+encodeURIComponent(state.invoiceSale);
 if(view==='saleReturns'&&state.returnSaleId)route+='/'+encodeURIComponent(state.returnSaleId);
 if(location.hash!=='#'+route)history.pushState({view},'', '#'+route);
 render();if(isMobile())document.getElementById('drawer').classList.remove('open')
}
function render(){document.body.classList.toggle('pos-mode',state.view==='pos');renderNav();const p=document.getElementById('page');p.innerHTML=!hasPageAccess(state.view)?accessDeniedView(state.view):(views[state.view]?views[state.view]():views.home());bindView();document.getElementById('userNameTop').textContent=account.owner||account.username;document.getElementById('avatar').textContent=(account.owner||account.username||'E').slice(0,1).toUpperCase()}
function pageHead(k,t,sub,actions=''){return `<div class="page-head"><div><div class="eyebrow">${k}</div><h1>${t}</h1>${sub?`<div class="muted small" style="margin-top:5px">${sub}</div>`:''}</div><div class="actions">${actions}</div></div>`}
const views={
 home(){
  let from=state.homeFrom||today(),to=state.homeTo||today();
  if(from>to){let t=from;from=to;to=t}
  const inRange=d=>{let x=(d||'').slice(0,10);return x>=from&&x<=to};
  const sales=data.sales.filter(s=>s.status!=='cancelled'&&inRange(s.date));
  const returns=data.returns.filter(r=>inRange(r.date));
  const purchases=data.purchases.filter(x=>inRange(x.date));
  const expenses=data.expenses.filter(x=>inRange(x.date));
  const totalSales=sales.reduce((a,s)=>a+s.total,0);
  const totalReturns=returns.reduce((a,r)=>a+r.total,0);
  const totalPurchases=purchases.reduce((a,p)=>a+p.total,0);
  const totalExpenses=expenses.reduce((a,e)=>a+e.amount,0);
  const net=totalSales-totalReturns;
  const invoiceDue=data.customers.reduce((a,c)=>a+Number(c.credit||0),0);
  const purchaseDue=0;
  const profit=sales.reduce((a,s)=>a+s.items.reduce((z,i)=>{let p=data.products.find(p=>p.id===i.productId);return z+(i.price-(p?.cost||0))*i.qty},0),0)-totalExpenses;
  const recent=sales.slice().sort((a,b)=>new Date(b.date)-new Date(a.date)).slice(0,6);
  const low=data.products.filter(p=>p.stock<=p.low).slice(0,6);
  const days=[]; let cur=new Date(from+'T00:00:00'), stop=new Date(to+'T00:00:00');
  let span=Math.max(1,Math.ceil((stop-cur)/86400000)+1); let step=Math.max(1,Math.ceil(span/12));
  for(let d=new Date(cur),i=0;d<=stop&&days.length<12;d.setDate(d.getDate()+step),i++){
    let ds=d.toISOString().slice(0,10), val=sales.filter(s=>s.date.slice(0,10)===ds).reduce((a,s)=>a+s.total,0);days.push({ds,val});
  }
  let max=Math.max(1,...days.map(x=>x.val));
  return `<div class="home-dashboard">
    <div class="home-head"><div><div class="eyebrow">EazyPOS · ${esc(account.business)}</div><div class="home-title">Welcome ${esc(account.business)} 👋</div><div class="muted small" style="margin-top:5px">${esc(account.owner||account.username)} · ${from===to?new Date(from+'T00:00:00').toLocaleDateString():new Date(from+'T00:00:00').toLocaleDateString()+' — '+new Date(to+'T00:00:00').toLocaleDateString()}</div></div>
      <div class="home-tools"><div class="date-control"><span class="small" style="font-weight:900">From</span><input class="input" type="date" value="${from}" onchange="state.homeFrom=this.value;render()"><span class="small" style="font-weight:900">To</span><input class="input" type="date" value="${to}" onchange="state.homeTo=this.value;render()"></div><button class="btn" onclick="state.homeFrom=today();state.homeTo=today();render()">Today</button><button class="btn primary" onclick="go('pos');ensureRegister()">＋ Add New Sale</button></div>
    </div>
    <div class="metric-grid">
      <div class="metric-card"><div class="metric-top"><div class="metric-icon">🛒</div><div class="metric-label">Total Sales</div></div><div class="metric-value">${money(totalSales)}</div></div>
      <div class="metric-card green"><div class="metric-top"><div class="metric-icon">▣</div><div class="metric-label">Net Sales</div></div><div class="metric-value">${money(net)}</div></div>
      <div class="metric-card warn"><div class="metric-top"><div class="metric-icon">▤</div><div class="metric-label">Invoice Due</div></div><div class="metric-value">${money(invoiceDue)}</div></div>
      <div class="metric-card red"><div class="metric-top"><div class="metric-icon">↔</div><div class="metric-label">Total Sell Return</div></div><div class="metric-value">${money(totalReturns)}</div></div>
      <div class="metric-card"><div class="metric-top"><div class="metric-icon">↓</div><div class="metric-label">Total Purchase</div></div><div class="metric-value">${money(totalPurchases)}</div></div>
      <div class="metric-card warn"><div class="metric-top"><div class="metric-icon">△</div><div class="metric-label">Purchase Due</div></div><div class="metric-value">${money(purchaseDue)}</div></div>
      <div class="metric-card red"><div class="metric-top"><div class="metric-icon">↩</div><div class="metric-label">Total Purchase Return</div></div><div class="metric-value">${money(0)}</div></div>
      <div class="metric-card red"><div class="metric-top"><div class="metric-icon">$</div><div class="metric-label">Expense</div></div><div class="metric-value">${money(totalExpenses)}</div></div>
    </div>
    <div class="home-lists">
      <div class="card home-chart"><div class="row"><div><h2>Sales Overview</h2><div class="muted small">${from===to?'Selected day':'Selected period'} · ${sales.length} sale(s) · Net ${money(net)}</div></div><button class="btn" onclick="go('sales')">View Sales</button></div><div class="mini-bars">${days.map(x=>`<div class="mini-bar" style="height:${Math.max(8,Math.round((x.val/max)*150))}px"><span>${new Date(x.ds+'T00:00:00').toLocaleDateString(undefined,{day:'2-digit',month:'short'})}</span></div>`).join('')}</div></div>
      <div class="card"><div class="row"><div><h2>Business Snapshot</h2><div class="muted small">Quick view for the selected period</div></div><button class="btn" onclick="go('reports')">Reports</button></div><div class="summary-row"><span>Gross Profit</span><b>${money(profit)}</b></div><div class="summary-row"><span>Products</span><b>${data.products.length}</b></div><div class="summary-row"><span>Low Stock</span><b>${low.length}</b></div><div class="summary-row"><span>Customers</span><b>${data.customers.length}</b></div></div>
    </div>
    <div class="home-lists">
      <div class="card home-section"><div class="row"><div><h2>Recent Sales</h2><div class="muted small">Filtered by selected date</div></div><button class="btn" onclick="go('sales')">View all</button></div>${saleRows(recent)}</div>
      <div class="card home-section"><div class="row"><div><h2>Low Stock</h2><div class="muted small">Products needing attention</div></div><button class="btn" onclick="go('stock')">Manage</button></div>${low.map(p=>`<div class="summary-row"><span>${esc(p.name)}</span><b>${p.stock} ${esc(p.unit)}</b></div>`).join('')||`<div class="empty">All stock levels look good.</div>`}</div>
    </div>
  </div>`;
 },

};
