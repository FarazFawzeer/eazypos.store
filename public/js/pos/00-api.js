/* EazyPOS · Secure client for the Laravel API.
 *
 * Every call goes to this application's own server with the session cookie
 * and CSRF token. Calls resolve to { data, error } so screens can handle
 * failures the same way everywhere. The business is always taken from the
 * signed-in session on the server; nothing here can choose another business.
 */
const eazyApi=(()=>{
 const csrf=()=>document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
 const id=value=>encodeURIComponent(String(value??''));

 async function request(method,url,body){
  let response;
  try{
   response=await fetch(url,{
    method,
    credentials:'same-origin',
    headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest'},
    body:body===undefined?undefined:JSON.stringify(body)
   });
  }catch(networkError){
   return {data:null,error:{message:'Unable to reach EazyPOS. Check your internet connection and try again.'}};
  }
  let payload=null;
  try{payload=await response.json()}catch(_){payload=null}
  if(response.status===401){
   const message=payload?.message||'Your session has expired. Please sign in again.';
   setTimeout(()=>{location.href='/login'},1200);
   return {data:null,error:{message,status:401}};
  }
  if(response.status===419)return {data:null,error:{message:'Your session expired. Please refresh the page and try again.',status:419}};
  if(!response.ok)return {data:null,error:{message:payload?.message||'Something went wrong. Please try again.',status:response.status}};
  return {data:payload,error:null};
 }

 const get=url=>request('GET',url);
 const post=(url,body)=>request('POST',url,body??{});
 const put=(url,body)=>request('PUT',url,body??{});
 const del=(url,body)=>request('DELETE',url,body);

 /* Business operations keep the names used by the screens (complete_sale,
  * create_purchase, ...). Each one maps to its own Laravel endpoint. */
 async function rpc(name,args={}){
  const a={};
  Object.keys(args||{}).forEach(k=>{a[k.replace(/^p_/,'')]=args[k]});
  delete a.business_id;
  switch(name){
   case 'complete_sale':return post('/api/sales',a);
   case 'update_sale':return put('/api/sales/'+id(a.sale_id),a);
   case 'delete_sale':return del('/api/sales/'+id(a.sale_id));
   case 'process_sale_return':return post('/api/sales/'+id(a.sale_id)+'/returns',a);
   case 'create_purchase':return post('/api/purchases',a);
   case 'update_purchase':return put('/api/purchases/'+id(a.purchase_id),a);
   case 'delete_purchase':return del('/api/purchases/'+id(a.purchase_id));
   case 'process_purchase_return':return post('/api/purchases/'+id(a.purchase_id)+'/returns',a);
   case 'create_expense':return post('/api/expenses',a);
   case 'update_expense':return put('/api/expenses/'+id(a.expense_id),a);
   case 'delete_expense':return del('/api/expenses/'+id(a.expense_id));
   case 'create_expense_category':return post('/api/expense-categories',{name:a.name});
   case 'update_expense_category':return put('/api/expense-categories/'+id(a.category_id),{name:a.name});
   case 'delete_expense_category':return del('/api/expense-categories/'+id(a.category_id));
   case 'adjust_stock':return post('/api/products/'+id(a.product_id)+'/adjust-stock',a);
   case 'create_shipment':return post('/api/shipments',a);
   case 'update_shipment':return put('/api/shipments/'+id(a.shipment_id),a);
   case 'change_shipment_status':return put('/api/shipments/'+id(a.shipment_id)+'/status',{status:a.status});
   case 'link_shipment_to_sale':return put('/api/shipments/'+id(a.shipment_id)+'/sale',{sale_id:a.sale_id});
   case 'delete_shipment':{const r=await del('/api/shipments/'+id(a.shipment_id));return {data:r.data?!!r.data.deleted:null,error:r.error};}
   case 'update_business_user':return put('/api/staff/'+id(a.business_user_id),{full_name:a.full_name,username:a.username,role:a.role,permissions:a.permissions});
   default:return {data:null,error:{message:'Unknown action: '+name}};
  }
 }

 return {get,post,put,del,rpc};
})();

/* When outgoing email is not set up yet, show the invitation link so the
 * owner can send it to the staff member another way (WhatsApp, SMS, ...). */
function showInviteLink(email,url){
 modal(`<div class="modal-head"><div><div class="eyebrow">INVITATION CREATED</div><h2>Share the invitation link</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="muted small" style="margin-bottom:12px">Email delivery is not set up yet, so send this secure link to <b>${esc(email)}</b> yourself. It expires in 7 days and can be used once.</div><div class="field"><label>INVITATION LINK</label><input class="input" id="inviteLinkField" value="${esc(url)}" readonly onclick="this.select()"></div><div class="modal-actions"><button class="btn" onclick="closeModal()">Close</button><button class="btn primary" onclick="copyInviteLink()">Copy Link</button></div>`);
}
function copyInviteLink(){
 const field=document.getElementById('inviteLinkField');if(!field)return;
 field.select();
 if(navigator.clipboard)navigator.clipboard.writeText(field.value).then(()=>toast('Invitation link copied')).catch(()=>{document.execCommand('copy');toast('Invitation link copied')});
 else{document.execCommand('copy');toast('Invitation link copied')}
}
