/* EazyPOS · Shipments: details, edit, status, delete and sale link. */
/* ===== EazyPOS Shipments: details / edit / status / delete / sale-link layer ===== */
function shipmentStatusLabel(status){
 const m={pending:'Pending',processing:'Processing',shipped:'Shipped',in_transit:'In Transit',delivered:'Delivered',cancelled:'Cancelled',returned:'Returned'};
 return m[status]||status||'Pending';
}
function shipmentPillClass(status){return status==='delivered'?'good':(status==='cancelled'||status==='returned'?'danger':'warn')}
function shipmentLocalDateTime(value){
 if(!value)return '';
 const d=new Date(value); if(Number.isNaN(d.getTime()))return '';
 const pad=n=>String(n).padStart(2,'0');
 return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
function shipmentFormatDate(value){return value?new Date(value).toLocaleString():'—'}
function shipmentById(id){return (data.shipments||[]).find(x=>String(x.id)===String(id));}

async function openShipmentDetails(id){
 const s=shipmentById(id);
 if(!s){toast('Shipment not found');return;}
 const sale=(data.sales||[]).find(x=>String(x.id)===String(s.saleId));
 const linkedSale=sale?`${sale.invoice||sale.id} · ${money(sale.total)}`:'Not linked';
 const canManage=hasPageAccess('shipments');
 modal(`<div class="modal-head"><div><div class="eyebrow">SHIPMENT</div><h2 style="margin:4px 0 0">${esc(s.number)}</h2></div><button class="close" onclick="closeModal()">×</button></div>
 <div class="card" style="box-shadow:none;border:1px solid var(--line);margin-bottom:14px">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap"><div><div class="muted">Current status</div><span class="pill ${shipmentPillClass(s.status)}">${esc(shipmentStatusLabel(s.status))}</span></div><div style="min-width:220px"><label class="muted" style="display:block;margin-bottom:6px">CHANGE STATUS</label><select class="select" id="shipmentDetailStatus" ${canManage?'':'disabled'} onchange="changeShipmentStatus('${esc(s.id)}',this.value)">${['pending','processing','shipped','in_transit','delivered','cancelled','returned'].map(v=>`<option value="${v}" ${s.status===v?'selected':''}>${shipmentStatusLabel(v)}</option>`).join('')}</select></div></div>
 </div>
 <div class="form-grid">
  <div class="field"><label>CUSTOMER</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(s.customer||'Walk-in Customer')}</div></div>
  <div class="field"><label>LINKED SALE</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(linkedSale)}</div></div>
  <div class="field"><label>SHIPPING DATE</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(shipmentFormatDate(s.shippingDate))}</div></div>
  <div class="field"><label>EXPECTED DELIVERY</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(shipmentFormatDate(s.expectedDeliveryDate))}</div></div>
  <div class="field"><label>CARRIER</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(s.carrier||'—')}</div></div>
  <div class="field"><label>TRACKING NUMBER</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(s.trackingNumber||'—')}</div></div>
  <div class="field"><label>SHIPPING COST</label><div class="input" style="display:flex;align-items:center;min-height:42px">${money(s.shippingCost)}</div></div>
  <div class="field"><label>DELIVERED AT</label><div class="input" style="display:flex;align-items:center;min-height:42px">${esc(shipmentFormatDate(s.deliveredAt))}</div></div>
  <div class="field full"><label>SHIPPING ADDRESS</label><div class="input" style="min-height:70px;white-space:pre-wrap">${esc(s.shippingAddress||'—')}</div></div>
  <div class="field full"><label>NOTES</label><div class="input" style="min-height:70px;white-space:pre-wrap">${esc(s.notes||'—')}</div></div>
 </div>
 <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;flex-wrap:wrap">
  ${canManage&&!s.saleId?`<button class="btn" onclick="linkShipmentSale('${esc(s.id)}')">Link Sale</button>`:''}
  ${canManage?`<button class="btn" onclick="editShipment('${esc(s.id)}')">Edit Shipment</button><button class="btn" style="color:#d84a3a" onclick="deleteShipment('${esc(s.id)}')">Delete</button>`:''}
 </div>`);
}

function editShipment(id){
 const s=shipmentById(id); if(!s){toast('Shipment not found');return;}
 const customerOptions=(data.customers||[]).map(c=>`<option value="${esc(c.id)}" ${String(c.id)===String(s.customerId)?'selected':''}>${esc(c.name)}</option>`).join('');
 const statusOptions=['pending','processing','shipped','in_transit','delivered','cancelled','returned'].map(v=>`<option value="${v}" ${s.status===v?'selected':''}>${shipmentStatusLabel(v)}</option>`).join('');
 modal(`<div class="modal-head"><h2>Edit Shipment</h2><button class="close" onclick="closeModal()">×</button></div>
 <div class="muted" style="margin-bottom:14px">${esc(s.number)}</div>
 <div class="form-grid">
  <div class="field"><label>CUSTOMER</label><select class="select" id="eshCustomer"><option value="">Walk-in Customer</option>${customerOptions}</select></div>
  <div class="field"><label>STATUS</label><select class="select" id="eshStatus">${statusOptions}</select></div>
  <div class="field"><label>SHIPPING DATE</label><input class="input" type="datetime-local" id="eshShippingDate" value="${shipmentLocalDateTime(s.shippingDate)}"></div>
  <div class="field"><label>EXPECTED DELIVERY</label><input class="input" type="datetime-local" id="eshExpectedDate" value="${shipmentLocalDateTime(s.expectedDeliveryDate)}"></div>
  <div class="field"><label>CARRIER</label><input class="input" id="eshCarrier" value="${esc(s.carrier)}" placeholder="DHL, Domex, PickMe..."></div>
  <div class="field"><label>TRACKING NUMBER</label><input class="input" id="eshTracking" value="${esc(s.trackingNumber)}"></div>
  <div class="field"><label>SHIPPING COST</label><input class="input" type="number" min="0" step="0.01" id="eshCost" value="${Number(s.shippingCost||0)}"></div>
  <div class="field full"><label>SHIPPING ADDRESS</label><textarea class="textarea" id="eshAddress">${esc(s.shippingAddress)}</textarea></div>
  <div class="field full"><label>NOTES</label><textarea class="textarea" id="eshNotes">${esc(s.notes)}</textarea></div>
 </div>
 <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px"><button class="btn" onclick="openShipmentDetails('${esc(s.id)}')">Cancel</button><button class="btn primary" id="editShipmentSaveBtn" onclick="saveEditedShipment('${esc(s.id)}')">Save Changes</button></div>`);
}

async function saveEditedShipment(id){
 const s=shipmentById(id); if(!s||!account?.businessId)return;
 const cost=Number(document.getElementById('eshCost')?.value||0); if(cost<0){toast('Shipping cost cannot be negative');return;}
 const btn=document.getElementById('editShipmentSaveBtn'); if(btn){btn.disabled=true;btn.textContent='Saving...';}
 try{
  const {error}=await eazyApi.rpc('update_shipment',{
   p_business_id:account.businessId,p_shipment_id:id,
   p_sale_id:s.saleId||null,
   p_customer_id:document.getElementById('eshCustomer')?.value||null,
   p_shipping_date:document.getElementById('eshShippingDate')?.value?new Date(document.getElementById('eshShippingDate').value).toISOString():null,
   p_expected_delivery_date:document.getElementById('eshExpectedDate')?.value?new Date(document.getElementById('eshExpectedDate').value).toISOString():null,
   p_shipping_address:document.getElementById('eshAddress')?.value.trim()||null,
   p_carrier:document.getElementById('eshCarrier')?.value.trim()||null,
   p_tracking_number:document.getElementById('eshTracking')?.value.trim()||null,
   p_shipping_cost:cost,p_status:document.getElementById('eshStatus')?.value||'pending',p_notes:document.getElementById('eshNotes')?.value.trim()||null
  });
  if(error){apiError('Unable to update shipment',error);return;}
  await loadBusinessData(); closeModal(); toast('Shipment updated successfully'); render();
 }catch(error){apiError('Unable to update shipment',error)}finally{if(btn){btn.disabled=false;btn.textContent='Save Changes';}}
}

async function changeShipmentStatus(id,status){
 if(!account?.businessId)return;
 try{
  const {error}=await eazyApi.rpc('change_shipment_status',{p_business_id:account.businessId,p_shipment_id:id,p_status:status});
  if(error){apiError('Unable to change shipment status',error);return;}
  await loadBusinessData(); closeModal(); toast('Shipment status updated'); render();
 }catch(error){apiError('Unable to change shipment status',error)}
}

async function deleteShipment(id){
 const s=shipmentById(id); if(!s||!account?.businessId)return;
 if(!confirm(`Delete shipment ${s.number}? This cannot be undone.`))return;
 try{
  const {data:ok,error}=await eazyApi.rpc('delete_shipment',{p_business_id:account.businessId,p_shipment_id:id});
  if(error){apiError('Unable to delete shipment',error);return;}
  if(!ok){toast('Shipment was not found');return;}
  await loadBusinessData(); closeModal(); toast('Shipment deleted'); render();
 }catch(error){apiError('Unable to delete shipment',error)}
}

function linkShipmentSale(id){
 const s=shipmentById(id); if(!s)return;
 const options=(data.sales||[]).filter(x=>!x.shipmentId).map(x=>`<option value="${esc(x.id)}">${esc(x.invoice||x.id)} · ${esc(x.customer||'Walk-in Customer')} · ${money(x.total)}</option>`).join('');
 modal(`<div class="modal-head"><h2>Link Shipment to Sale</h2><button class="close" onclick="closeModal()">×</button></div><p class="muted">${esc(s.number)}</p><div class="field"><label>SALE</label><select class="select" id="shipmentSaleSelect"><option value="">Select a sale...</option>${options}</select></div><div style="display:flex;justify-content:flex-end;margin-top:14px"><button class="btn primary" onclick="saveShipmentSaleLink('${esc(id)}')">Link Sale</button></div>`);
}
async function saveShipmentSaleLink(id){
 const saleId=document.getElementById('shipmentSaleSelect')?.value; if(!saleId){toast('Select a sale first');return;}
 try{
  const {error}=await eazyApi.rpc('link_shipment_to_sale',{p_business_id:account.businessId,p_shipment_id:id,p_sale_id:saleId});
  if(error){apiError('Unable to link shipment to sale',error);return;}
  await loadBusinessData(); closeModal(); toast('Shipment linked to sale'); render();
 }catch(error){apiError('Unable to link shipment to sale',error)}
}

/* Replace the simple shipment table with interactive rows while preserving the existing date filter. */
shipmentView=function(){
 const r=range('shipments'),list=data.shipments.filter(x=>dateInRange(x.date||x.createdAt||'',r.from,r.to));
 return `<div class="page">${pageHead('Sell','Shipments','Track deliveries and collections.','<button class="btn primary" onclick="addShipment()">＋ Add Shipment</button>')}${dateFilter('shipments','Shipment date')}<div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Shipment</th><th>Customer</th><th>Date</th><th>Carrier</th><th>Tracking</th><th>Status</th><th>Cost</th><th></th></tr></thead><tbody>${list.map(x=>`<tr style="cursor:pointer" onclick="openShipmentDetails('${esc(x.id)}')"><td><b>${esc(x.number)}</b></td><td>${esc(x.customer)}</td><td>${x.date?new Date(x.date).toLocaleString():'—'}</td><td>${esc(x.carrier||'—')}</td><td>${esc(x.trackingNumber||'—')}</td><td><span class="pill ${shipmentPillClass(x.status)}">${esc(shipmentStatusLabel(x.status))}</span></td><td>${money(x.shippingCost)}</td><td><button class="btn" onclick="event.stopPropagation();openShipmentDetails('${esc(x.id)}')">Open</button></td></tr>`).join('')||'<tr><td colspan="8"><div class="empty">No shipments in this date range.</div></td></tr>'}</tbody></table></div></div></div>`;
};

