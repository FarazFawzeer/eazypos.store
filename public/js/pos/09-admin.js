/* EazyPOS · Users, payment accounts, quotations, shipments form, settings and UI primitives. */
function usersView(){
 const members=data.users||[];
 const invites=(data.invitations||[]).filter(i=>String(i.status).toLowerCase()==='pending');
 const canManage=['owner','admin'].includes(String(account?.role||'').toLowerCase())||!!account?.permissions?.users;
 return `<div class="page">${pageHead('Administration','User Management','Manage staff access and roles.',canManage?'<button class="btn primary" onclick="addUser()">＋ Invite User</button>':'')}
 <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>
 ${members.map(u=>{
   const isOwner=String(u.role).toLowerCase()==='owner';
   const isSelf=String(u.userId)===String(account?.userId||'');
   return `<tr><td><b>${esc(u.name)}</b></td><td>${esc(u.username||'—')}</td><td>${esc(u.role)}</td><td><span class="pill ${String(u.status).toLowerCase()==='active'?'good':''}">${esc(u.status)}</span></td><td style="white-space:nowrap">${canManage?`<button class="btn" onclick="editUser('${u.id}')">Edit</button>${!isOwner&&!isSelf?` <button class="btn danger" onclick="deleteStaffUser('${esc(u.userId)}',${esc(JSON.stringify(u.name))},'')">Delete</button>`:''}`:'<span class="muted small">View only</span>'}</td></tr>`;
 }).join('')||'<tr><td colspan="5"><div class="empty">No staff members yet.</div></td></tr>'}
 </tbody></table></div></div>
 ${invites.length?`<div class="card" style="margin-top:16px"><div class="row" style="margin-bottom:10px"><div><h2 style="margin:0">Pending Invitations</h2><div class="muted small">Staff who have been invited but have not completed account setup.</div></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Email</th><th>Role</th><th>Status</th><th>Expires</th><th>Actions</th></tr></thead><tbody>${invites.map(i=>`<tr><td><b>${esc(i.email)}</b></td><td>${esc(i.role)}</td><td><span class="pill">${esc(i.status)}</span></td><td>${i.expiresAt?new Date(i.expiresAt).toLocaleDateString():'—'}</td><td>${canManage?`<button class="btn danger" onclick="deleteStaffUser('',${esc(JSON.stringify(i.email))},${esc(JSON.stringify(i.email))})">Delete</button>`:'—'}</td></tr>`).join('')}</tbody></table></div></div>`:''}
 </div>`;
}
function addUser(){
 const perms={sales:true,purchases:false,products:false,reports:false,expenses:false,stock:false,users:false,shipments:false};
 modal(`<div class="modal-head"><div><h2>Invite User</h2><div class="muted small">Send a secure EazyPOS invitation by email.</div></div><button class="close" onclick="closeModal()">×</button></div>
 <div class="form-grid">
 <div class="field"><label>FULL NAME *</label><input class="input" id="uName" placeholder="Staff member name"></div>
 <div class="field"><label>USERNAME</label><input class="input" id="uUser" placeholder="Optional username"></div>
 <div class="field"><label>EMAIL *</label><input class="input" id="uEmail" type="email" placeholder="staff@example.com"></div>
 <div class="field"><label>ROLE</label><select class="select" id="uRole" onchange="applyUserRoleDefaults()"><option value="cashier">Cashier</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
 </div>
 <div class="field" style="margin-top:14px"><label>PERMISSIONS</label><div class="grid grid2" style="margin-top:8px">
 ${['sales','purchases','products','reports','expenses','stock','users','shipments'].map(k=>`<label class="check-row"><input type="checkbox" id="perm_${k}" ${perms[k]?'checked':''}> <span>${k.charAt(0).toUpperCase()+k.slice(1)}</span></label>`).join('')}
 </div></div>
 <div class="overlay-note" style="margin-top:14px">The staff member will receive an email invitation and create their own password. No password is entered or stored here.</div>
 <button class="btn primary" style="width:100%;margin-top:14px" onclick="sendStaffInvitation()">Send Invitation</button>`);
}
function applyUserRoleDefaults(){
 const role=document.getElementById('uRole')?.value;
 const defaults=role==='admin'?{sales:true,purchases:true,products:true,reports:true,expenses:true,stock:true,users:true,shipments:true}:role==='manager'?{sales:true,purchases:true,products:true,reports:true,expenses:true,stock:true,users:false,shipments:true}:{sales:true,purchases:false,products:false,reports:false,expenses:false,stock:false,users:false,shipments:false};
 Object.keys(defaults).forEach(k=>{const el=document.getElementById('perm_'+k);if(el)el.checked=defaults[k]});
}
async function sendStaffInvitation(){
 const name=document.getElementById('uName')?.value.trim();
 const username=document.getElementById('uUser')?.value.trim();
 const email=document.getElementById('uEmail')?.value.trim().toLowerCase();
 const role=document.getElementById('uRole')?.value;
 if(!name||!email){toast('Full name and email are required');return}
 if(!/^\S+@\S+\.\S+$/.test(email)){toast('Enter a valid email address');return}
 const permissions={};['sales','purchases','products','reports','expenses','stock','users','shipments'].forEach(k=>permissions[k]=!!document.getElementById('perm_'+k)?.checked);
 const btn=document.querySelector('.modal .btn.primary');if(btn){btn.disabled=true;btn.textContent='Sending Invitation…'}
 try{
   const {data:result,error}=await eazyApi.post('/api/staff/invitations',{email,role,permissions,name,username});
   if(error)throw error;
   await loadBusinessData();render();
   if(result?.email_sent){closeModal();toast(`Invitation sent to ${email}`);}else{showInviteLink(email,result?.invite_url||'');}
 }catch(error){apiError('Unable to send staff invitation',error)}finally{if(btn){btn.disabled=false;btn.textContent='Send Invitation'}}
}
function editUser(id){
 let u=(data.users||[]).find(x=>String(x.id)===String(id));
 if(!u){toast('User not found');return}
 const canManage=['owner','admin'].includes(String(account?.role||'').toLowerCase())||!!account?.permissions?.users;
 const isOwner=String(u.role).toLowerCase()==='owner';
 const isSelf=String(u.userId)===String(account?.userId||'');
 const currentPermissions=Object.assign({sales:false,purchases:false,products:false,reports:false,expenses:false,stock:false,users:false,shipments:false},u.permissions||{});
 const permissionKeys=['sales','purchases','products','reports','expenses','stock','users','shipments'];
 const permissionsHtml=permissionKeys.map(k=>`<label class="check-row"><input type="checkbox" id="edit_perm_${k}" ${currentPermissions[k]?'checked':''}> <span>${k.charAt(0).toUpperCase()+k.slice(1)}</span></label>`).join('');
 modal(`<div class="modal-head"><div><h2>Edit User</h2><div class="muted small">Manage this staff member's role and module access.</div></div><button class="close" onclick="closeModal()">×</button></div>
 <div class="form-grid"><div class="field"><label>NAME</label><input class="input" id="euName" value="${esc(u.name)}"></div><div class="field"><label>ROLE</label><select class="select" id="euRole"><option value="cashier" ${u.role.toLowerCase()==='cashier'?'selected':''}>Cashier</option><option value="manager" ${u.role.toLowerCase()==='manager'?'selected':''}>Manager</option><option value="admin" ${u.role.toLowerCase()==='admin'?'selected':''}>Admin</option></select></div></div>
 <div class="field" style="margin-top:14px"><label>PERMISSIONS</label><div class="grid grid2" style="margin-top:8px">${permissionsHtml}</div></div>
 <div class="overlay-note">Changes are saved securely to the business staff record. Owners cannot be removed.</div>
 <div class="row" style="gap:10px;margin-top:14px"><button class="btn primary" style="flex:1" onclick="saveEditedUser('${u.id}')">Save Changes</button>${canManage&&!isOwner&&!isSelf?`<button class="btn danger" style="flex:1" onclick="deleteStaffUser('${esc(u.userId)}',${esc(JSON.stringify(u.name))})">Delete User</button>`:''}</div>`)
}
async function saveEditedUser(id){
 const u=(data.users||[]).find(x=>String(x.id)===String(id));if(!u)return;
 const name=document.getElementById('euName')?.value.trim();const role=document.getElementById('euRole')?.value;
 if(!name){toast('Name is required');return}
 const permissionKeys=['sales','purchases','products','reports','expenses','stock','users','shipments'];
 const permissions={};permissionKeys.forEach(k=>permissions[k]=!!document.getElementById('edit_perm_'+k)?.checked);
 try{
  const {error}=await eazyApi.rpc('update_business_user',{p_business_user_id:id,p_full_name:name,p_username:u.username||null,p_role:role,p_permissions:permissions});
  if(error)throw error;
  await loadBusinessData();closeModal();toast('User updated');render();
 }catch(error){apiError('Unable to update user',error)}
}
async function deleteStaffUser(userId,name,email=''){
 if(!userId&&!email){toast('User information is missing.');return}
 if(userId&&userId===account?.userId){toast('You cannot delete your own account from here.');return}
 const label=name||email||'this user';
 if(!confirm(`Delete ${label} permanently?\n\nThis removes their EazyPOS access and, when safe, their EazyPOS login. This cannot be undone.`))return;
 try{
  const {error}=await eazyApi.post('/api/staff/remove',{user_id:userId||null,email:email||null});
  if(error)throw error;
  await loadBusinessData();
  closeModal();
  toast('User deleted successfully');
  render();
 }catch(error){apiError('Unable to delete user',error)}
}
function paymentAccountsView(){return `<div class="page">${pageHead('Finance','Payment Accounts','Keep the payment methods used by the POS.')}<div class="grid grid2"><div class="card"><h2>Cash</h2><div class="muted">Cash register payments</div></div><div class="card"><h2>Card</h2><div class="muted">Card terminal payments</div></div><div class="card"><h2>Bank</h2><div class="muted">Bank transfer payments</div></div><div class="card"><h2>Credit</h2><div class="muted">Customer credit sales</div></div></div></div>`}
function quotationView(){return `<div class="page">${pageHead('Sell','Quotations','Create and manage quotations.','<button class="btn primary" onclick="addQuotation()">＋ New Quotation</button>')}<div class="card">${data.quotations.map(q=>`<div class="summary-row"><div><b>${esc(q.number)}</b><div class="muted">${esc(q.customer)} · ${q.date}</div></div><b>${money(q.total)}</b></div>`).join('')||'<div class="empty">No quotations yet.</div>'}</div></div>`}
function addQuotation(){modal(`<div class="modal-head"><h2>New Quotation</h2><button class="close" onclick="closeModal()">×</button></div><div class="form-grid"><div class="field"><label>CUSTOMER</label><input class="input" id="qCustomer" value="Walk-in Customer"></div><div class="field"><label>AMOUNT</label><input class="input" id="qAmount" type="number"></div></div><button class="btn primary" style="width:100%;margin-top:14px" onclick="saveQuotation()">Save Quotation</button>`)}
async function saveQuotation(){const a=Number(document.getElementById('qAmount').value||0);if(!Number.isFinite(a)||a<0){toast('Enter a valid quotation amount');return}const {data:row,error}=await eazyApi.post('/api/quotations',{customer_name:document.getElementById('qCustomer').value.trim(),amount:a});if(error){apiError('Unable to save quotation',error);return}await loadBusinessData();closeModal();toast(`Quotation ${row?.quotation_number||''} saved`);render()}
function shipmentView(){return `<div class="page">${pageHead('Sell','Shipments','Track deliveries and collections.','<button class="btn primary" onclick="addShipment()">＋ Add Shipment</button>')}<div class="card">${data.shipments.map(s=>`<div class="summary-row"><div><b>${s.number}</b><div class="muted">${esc(s.customer)} · ${s.status}</div></div><button class="btn" onclick="toast('Shipment opened')">Open</button></div>`).join('')||'<div class="empty">No shipments yet.</div>'}</div></div>`}
function addShipment(){
 const customerOptions=(data.customers||[]).map(c=>`<option value="${esc(c.id)}">${esc(c.name)}</option>`).join('');
 modal(`<div class="modal-head"><h2>Add Shipment</h2><button class="close" onclick="closeModal()">×</button></div>
 <div class="form-grid">
   <div class="field"><label>CUSTOMER</label><select class="select" id="shCustomer"><option value="">Walk-in Customer</option>${customerOptions}</select></div>
   <div class="field"><label>STATUS</label><select class="select" id="shStatus"><option value="pending">Pending</option><option value="processing">Processing</option><option value="shipped">Shipped</option><option value="in_transit">In Transit</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option><option value="returned">Returned</option></select></div>
   <div class="field"><label>SHIPPING DATE</label><input class="input" type="datetime-local" id="shShippingDate"></div>
   <div class="field"><label>EXPECTED DELIVERY</label><input class="input" type="datetime-local" id="shExpectedDate"></div>
   <div class="field"><label>CARRIER</label><input class="input" id="shCarrier" placeholder="DHL, Domex, PickMe..."></div>
   <div class="field"><label>TRACKING NUMBER</label><input class="input" id="shTracking"></div>
   <div class="field"><label>SHIPPING COST</label><input class="input" type="number" min="0" step="0.01" id="shCost" value="0"></div>
   <div class="field full"><label>SHIPPING ADDRESS</label><textarea class="textarea" id="shAddress"></textarea></div>
   <div class="field full"><label>NOTES</label><textarea class="textarea" id="shNotes"></textarea></div>
 </div>
 <button class="btn primary" style="width:100%;margin-top:14px" onclick="saveShipment()">Save Shipment</button>`);
}
async function saveShipment(){
 if(!account?.businessId){toast('Business is not ready');return}
 const customerId=document.getElementById('shCustomer')?.value||null;
 const status=document.getElementById('shStatus')?.value||'pending';
 const shippingDate=document.getElementById('shShippingDate')?.value||'';
 const expectedDate=document.getElementById('shExpectedDate')?.value||'';
 const shippingCost=Number(document.getElementById('shCost')?.value||0);
 if(shippingCost<0){toast('Shipping cost cannot be negative');return}
 const btn=[...document.querySelectorAll('#modalBox button')].find(b=>b.textContent.trim()==='Save Shipment');
 if(btn){btn.disabled=true;btn.textContent='Saving...'}
 try{
   const {data:result,error}=await eazyApi.rpc('create_shipment',{
     p_business_id:account.businessId,
     p_sale_id:null,
     p_customer_id:customerId,
     p_shipping_date:shippingDate?new Date(shippingDate).toISOString():null,
     p_expected_delivery_date:expectedDate?new Date(expectedDate).toISOString():null,
     p_shipping_address:document.getElementById('shAddress')?.value.trim()||null,
     p_carrier:document.getElementById('shCarrier')?.value.trim()||null,
     p_tracking_number:document.getElementById('shTracking')?.value.trim()||null,
     p_shipping_cost:shippingCost,
     p_status:status,
     p_notes:document.getElementById('shNotes')?.value.trim()||null
   });
   if(error){apiError('Unable to save shipment',error);return}
   await loadBusinessData();
   closeModal();
   toast(`Shipment ${result?.shipment_number||''} saved successfully`);
   render();
 }catch(error){apiError('Unable to save shipment',error)}
 finally{if(btn){btn.disabled=false;btn.textContent='Save Shipment'}}
}
function settingsView(){return `<div class="page">${pageHead('Settings','Settings','Business, currency, receipt and interface preferences.','<button class="btn primary" onclick="saveSettings()">Save Settings</button>')}<div class="card"><div class="form-grid"><div class="field"><label>BUSINESS NAME</label><input class="input" id="setBusiness" value="${esc(data.settings.business)}"></div><div class="field"><label>CURRENCY</label><select class="select" id="setCurrency">${Object.keys(currencySymbols).map(c=>`<option ${data.settings.currency===c?'selected':''}>${c}</option>`).join('')}</select></div><div class="field"><label>TAX %</label><input class="input" id="setTax" type="number" value="${data.settings.tax||0}"></div><div class="field"><label>RECEIPT FOOTER</label><input class="input" id="setFooter" value="${esc(data.settings.receiptFooter||'Thank you!')}"></div></div></div></div>`}
async function saveSettings(){const name=document.getElementById('setBusiness').value.trim()||data.settings.business,currency=document.getElementById('setCurrency').value;const tax=Number(document.getElementById('setTax').value||0),receiptFooter=document.getElementById('setFooter').value.trim();if(!Number.isFinite(tax)||tax<0||tax>100){toast('Tax % must be between 0 and 100');return}const {error}=await eazyApi.put('/api/settings',{name,currency,tax_rate:tax,receipt_footer:receiptFooter});if(error){apiError('Unable to save settings',error);return}data.settings.business=name;data.settings.currency=currency;await loadBusinessData();toast('Settings saved');render()}
function bindView(){if(state.view==='stock')renderStockBox();if(state.view==='addPurchase'){renderSupplierPicker();}}
function modal(html){document.getElementById('modalBox').innerHTML=html;document.getElementById('modal').classList.remove('hidden')}
function closeModal(){document.getElementById('modal').classList.add('hidden')}
function toast(msg){let t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');clearTimeout(window._toast);window._toast=setTimeout(()=>t.classList.remove('show'),2200)}
function quickSearch(){let q=document.getElementById('globalSearch').value.trim();if(q){state.productQuery=q;go('products')}}
function shareText(text){if(navigator.share)navigator.share({title:'EazyPOS',text});else navigator.clipboard?.writeText(text).then(()=>toast('Copied to clipboard'))}
async function logout(){await eazyApi.post('/logout');location.href='/'}
window.addEventListener('resize',()=>renderNav());
document.getElementById('modal').addEventListener('click',e=>{if(e.target.id==='modal')closeModal()});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
