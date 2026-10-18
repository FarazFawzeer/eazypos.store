/* EazyPOS · Expenses and expense categories. */
function expenseView(){
 const total=data.expenses.reduce((a,e)=>a+e.amount,0);
 const cash=data.expenses.filter(e=>e.payment==='Cash').reduce((a,e)=>a+e.amount,0);
 const cats=data.expenseCategories||[];
 return `<div class="page">${pageHead('Expenses','Expenses','Track business spending.','<button class="btn" onclick="manageExpenseCategories()">⚙ Categories</button><button class="btn primary" onclick="addExpense()">＋ Add Expense</button>')}<div class="grid grid3 stats"><div class="card"><div class="stat-label">Total Expenses</div><div class="stat-value">${money(total)}</div></div><div class="card"><div class="stat-label">Cash Expenses</div><div class="stat-value">${money(cash)}</div></div><div class="card"><div class="stat-label">Entries</div><div class="stat-value">${data.expenses.length}</div></div></div><div class="card" style="margin-top:14px"><div class="row" style="margin-bottom:12px"><div><h2>Expense History</h2><div class="muted small">Edit or delete expenses without breaking register totals.</div></div><div class="searchbar" style="margin:0;max-width:360px"><input class="input" id="expenseSearch" placeholder="Search expenses..." oninput="filterExpenseRows(this.value)"></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Expense</th><th>Category</th><th>Date</th><th>Payment</th><th>Amount</th><th style="text-align:right">Actions</th></tr></thead><tbody id="expenseRows">${expenseRows(data.expenses)}</tbody></table></div></div></div>`;
}
function expenseRows(list){return list.map(e=>`<tr><td><b>${esc(e.name)}</b>${e.note?`<div class="muted small">${esc(e.note)}</div>`:''}</td><td>${esc(e.category)}</td><td>${e.date?new Date(e.date).toLocaleDateString():''}</td><td>${esc(e.payment)}</td><td><b>${money(e.amount)}</b></td><td><div class="actions" style="justify-content:flex-end"><button class="btn" onclick="editExpense('${e.id}')">Edit</button><button class="btn danger" onclick="deleteExpenseConfirm('${e.id}')">Delete</button></div></td></tr>`).join('')||'<tr><td colspan="6"><div class="empty">No expenses found.</div></td></tr>'}
function filterExpenseRows(q){q=(q||'').trim().toLowerCase();const rows=data.expenses.filter(e=>[e.name,e.category,e.payment,e.note].join(' ').toLowerCase().includes(q));const el=document.getElementById('expenseRows');if(el)el.innerHTML=expenseRows(rows)}
function editExpense(id, selectedCategoryId=null, draft=null){
 const e=data.expenses.find(x=>String(x.id)===String(id)); if(!e)return;
 const cats=data.expenseCategories||[]; const payment=(e.payment||'Cash').toLowerCase().replace(' ','_');
 const d=e.date?new Date(e.date).toISOString().slice(0,10):today();
 modal(`<div class="modal-head"><div><div class="eyebrow">Expenses</div><h2>Edit Expense</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="form-grid"><div class="field"><label>EXPENSE DESCRIPTION</label><input class="input" id="exName" value="${esc(draft?.n??e.name)}"></div><div class="field"><label>CATEGORY</label><select class="select" id="exCat" onchange="expenseCategoryChanged(this.value,'edit','${e.id}')"><option value="">No category</option>${cats.map(c=>`<option value="${esc(c.id)}" ${String(c.id)===(String(selectedCategoryId??e.categoryId))?'selected':''}>${esc(c.name)}</option>`).join('')}<option value="__add_category__">＋ Add Category...</option></select></div><div class="field"><label>AMOUNT</label><input class="input" id="exAmount" type="number" min="0.01" step="0.01" value="${Number(draft?.a??e.amount).toFixed(2)}"></div><div class="field"><label>PAYMENT METHOD</label><select class="select" id="exPay"><option value="cash" ${(draft?.payment||payment)==='cash'?'selected':''}>Cash</option><option value="card" ${(draft?.payment||payment)==='card'?'selected':''}>Card</option><option value="bank_transfer" ${(draft?.payment||payment)==='bank_transfer'?'selected':''}>Bank Transfer</option><option value="cheque" ${(draft?.payment||payment)==='cheque'?'selected':''}>Cheque</option><option value="credit" ${(draft?.payment||payment)==='credit'?'selected':''}>Credit</option><option value="other" ${(draft?.payment||payment)==='other'?'selected':''}>Other</option></select></div><div class="field"><label>DATE</label><input class="input" id="exDate" type="date" value="${esc(draft?.date||d)}"></div><div class="field" style="grid-column:1/-1"><label>NOTES</label><textarea class="input" id="exNotes" rows="3">${esc(draft?.notes??(e.note||''))}</textarea></div></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn primary" onclick="saveEditedExpense('${e.id}')">Save Changes</button></div>`);
}
async function saveEditedExpense(id){
 const n=document.getElementById('exName')?.value.trim(), a=Number(document.getElementById('exAmount')?.value||0), categoryId=document.getElementById('exCat')?.value||null, payment=document.getElementById('exPay')?.value||'cash', date=document.getElementById('exDate')?.value, notes=document.getElementById('exNotes')?.value.trim()||null;
 if(!n||a<=0){toast('Enter expense description and amount');return}
 const openRegister=getOpenRegister();
 if(payment==='cash'&&!openRegister){state.pendingExpenseEdit={id,n,a,categoryId,payment,date,notes};openRegisterForExpenseEdit();return}
 const {data:result,error}=await eazyApi.rpc('update_expense',{p_business_id:account.businessId,p_expense_id:id,p_category_id:categoryId,p_description:n,p_amount:a,p_payment_method:payment,p_expense_date:date?`${date}T00:00:00`:new Date().toISOString(),p_notes:notes,p_register_id:payment==='cash'?(openRegister?.id||null):null});
 if(error){apiError('Unable to update expense',error);return}
 state.pendingExpenseEdit=null;await loadBusinessData();closeModal();toast('Expense updated');render();
}
function deleteExpenseConfirm(id){const e=data.expenses.find(x=>String(x.id)===String(id));if(!e)return;modal(`<div class="modal-head"><div><div class="eyebrow">Expense</div><h2>Delete Expense?</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="card" style="margin:12px 0"><b>${esc(e.name)}</b><div class="muted" style="margin-top:5px">${money(e.amount)} · ${esc(e.payment)}</div></div><div class="overlay-note">This will reverse any cash-register effect associated with this expense.</div><div class="actions" style="margin-top:16px;justify-content:flex-end"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn danger" onclick="deleteExpense('${e.id}')">Delete Expense</button></div>`)}
async function deleteExpense(id){const {data:result,error}=await eazyApi.rpc('delete_expense',{p_business_id:account.businessId,p_expense_id:id});if(error){apiError('Unable to delete expense',error);return}await loadBusinessData();closeModal();toast('Expense deleted');render()}
function manageExpenseCategories(){const cats=data.expenseCategories||[];modal(`<div class="modal-head"><div><div class="eyebrow">Expenses</div><h2>Expense Categories</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="row" style="gap:8px;margin-bottom:14px"><input class="input" id="newExpenseCat" placeholder="Add category name" style="flex:1"><button class="btn primary" onclick="createExpenseCategory()">＋ Add</button></div><div style="display:grid;gap:8px;max-height:360px;overflow:auto">${cats.map(c=>`<div class="summary-row"><div><b>${esc(c.name)}</b></div><div class="actions"><button class="btn" onclick="editExpenseCategory('${c.id}')">Edit</button><button class="btn danger" onclick="deleteExpenseCategory('${c.id}')">Delete</button></div></div>`).join('')||'<div class="empty">No expense categories yet.</div>'}</div>`)}
async function createExpenseCategory(){const name=document.getElementById('newExpenseCat')?.value.trim();if(!name){toast('Enter a category name');return}const {error}=await eazyApi.rpc('create_expense_category',{p_business_id:account.businessId,p_name:name});if(error){apiError('Unable to add category',error);return}await loadBusinessData();manageExpenseCategories();toast('Category added')}
function editExpenseCategory(id){const c=(data.expenseCategories||[]).find(x=>String(x.id)===String(id));if(!c)return;modal(`<div class="modal-head"><div><div class="eyebrow">Expenses</div><h2>Edit Category</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="field"><label>CATEGORY NAME</label><input class="input" id="editExpenseCat" value="${esc(c.name)}"></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="manageExpenseCategories()">Cancel</button><button class="btn primary" onclick="saveExpenseCategory('${c.id}')">Save Changes</button></div>`)}
async function saveExpenseCategory(id){const name=document.getElementById('editExpenseCat')?.value.trim();if(!name){toast('Enter a category name');return}const {error}=await eazyApi.rpc('update_expense_category',{p_business_id:account.businessId,p_category_id:id,p_name:name});if(error){apiError('Unable to update category',error);return}await loadBusinessData();manageExpenseCategories();toast('Category updated')}
async function deleteExpenseCategory(id){const c=(data.expenseCategories||[]).find(x=>String(x.id)===String(id));if(!c)return;modal(`<div class="modal-head"><div><div class="eyebrow">Expense Category</div><h2>Delete Category?</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="overlay-note">If this category is already used by historical expenses, EazyPOS will deactivate it instead of destroying the history.</div><div class="actions" style="margin-top:16px;justify-content:flex-end"><button class="btn" onclick="manageExpenseCategories()">Cancel</button><button class="btn danger" onclick="confirmDeleteExpenseCategory('${id}')">Delete Category</button></div>`)}
async function confirmDeleteExpenseCategory(id){const {error}=await eazyApi.rpc('delete_expense_category',{p_business_id:account.businessId,p_category_id:id});if(error){apiError('Unable to delete category',error);return}await loadBusinessData();manageExpenseCategories();toast('Category removed')}

function openRegisterForExpenseEdit(){
 const pending=state.pendingExpenseEdit;if(!pending)return;
 modal(`<div class="modal-head"><div><div class="eyebrow">Cash Register</div><h2>Open Register to Continue</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="overlay-note">This expense uses Cash, so an open register is required. Open one and the expense edit will continue automatically.</div><div class="field" style="margin-top:14px"><label>OPENING CASH</label><input class="input" id="expenseEditOpeningCash" type="number" min="0" step="0.01" value="0"></div><div class="actions" style="margin-top:16px;justify-content:flex-end"><button class="btn" onclick="state.pendingExpenseEdit=null;closeModal()">Cancel</button><button class="btn primary" onclick="openRegisterAndContinueExpenseEdit()">Open Register & Continue</button></div>`)
}
async function openRegisterAndContinueExpenseEdit(){const opening=Number(document.getElementById('expenseEditOpeningCash')?.value||0);if(opening<0){toast('Enter valid opening cash');return}const {data:row,error}=await eazyApi.post('/api/registers',{opening_cash:opening});if(error){apiError('Unable to open register',error);return}await loadBusinessData();const p=state.pendingExpenseEdit;state.pendingExpenseEdit=null;closeModal();await saveEditedExpense(p.id);}

const DEFAULT_EXPENSE_CATEGORIES=['Salary','Food & Beverages','Stationery','Transport','Rent','Utilities','Maintenance','Marketing','Other'];
async function ensureDefaultExpenseCategories(){
 const existing=data.expenseCategories||[];let changed=false;
 for(const name of DEFAULT_EXPENSE_CATEGORIES){
  if(existing.some(c=>String(c.name).trim().toLowerCase()===name.toLowerCase()))continue;
  const {error}=await eazyApi.rpc('create_expense_category',{p_business_id:account.businessId,p_name:name});
  if(error){console.warn('Could not create default expense category:',name,error);continue;}changed=true;
 }
 if(changed)await loadBusinessData();
}
function expenseCategoryOptions(selected=''){
 const cats=data.expenseCategories||[];
 return `<option value="">No category</option>${cats.map(c=>`<option value="${esc(c.id)}" ${String(c.id)===String(selected)?'selected':''}>${esc(c.name)}</option>`).join('')}<option value="__add_category__">＋ Add Category...</option>`;
}
function addExpense(draft={}){
 const open=async()=>{
  await ensureDefaultExpenseCategories();
  modal(`<div class="modal-head"><div><div class="eyebrow">Expenses</div><h2>Add Expense</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="form-grid"><div class="field"><label>EXPENSE DESCRIPTION</label><input class="input" id="exName" placeholder="Transport, Electricity, Rent..." value="${esc(draft.n||'')}"></div><div class="field"><label>CATEGORY</label><select class="select" id="exCat" onchange="expenseCategoryChanged(this.value,'add')">${expenseCategoryOptions(draft.categoryId||'')}</select></div><div class="field"><label>AMOUNT</label><input class="input" id="exAmount" type="number" min="0.01" step="0.01" placeholder="0.00" value="${draft.a?esc(draft.a):''}"></div><div class="field"><label>PAYMENT METHOD</label><select class="select" id="exPay"><option value="cash" ${draft.payment==='cash'?'selected':''}>Cash</option><option value="card" ${draft.payment==='card'?'selected':''}>Card</option><option value="bank_transfer" ${draft.payment==='bank_transfer'?'selected':''}>Bank Transfer</option><option value="cheque" ${draft.payment==='cheque'?'selected':''}>Cheque</option><option value="credit" ${draft.payment==='credit'?'selected':''}>Credit</option><option value="other" ${draft.payment==='other'?'selected':''}>Other</option></select></div><div class="field"><label>DATE</label><input class="input" id="exDate" type="date" value="${esc(draft.date||today())}"></div><div class="field" style="grid-column:1/-1"><label>NOTES</label><textarea class="input" id="exNotes" rows="3" placeholder="Optional notes">${esc(draft.notes||'')}</textarea></div></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn primary" onclick="saveExpense()">Save Expense</button></div>`);
 };
 open();
}
function expenseCategoryChanged(value,mode,id=null){
 if(value!=='__add_category__')return;
 const draft={n:document.getElementById('exName')?.value||'',a:document.getElementById('exAmount')?.value||'',categoryId:'',payment:document.getElementById('exPay')?.value||'cash',date:document.getElementById('exDate')?.value||today(),notes:document.getElementById('exNotes')?.value||''};
 state.expenseCategoryDraft={mode,id,draft};
 modal(`<div class="modal-head"><div><div class="eyebrow">Expenses</div><h2>Add Category</h2></div><button class="close" onclick="resumeExpenseCategory()">×</button></div><div class="field"><label>CATEGORY NAME</label><input class="input" id="inlineExpenseCategory" placeholder="e.g. Internet Bill" maxlength="80"></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="resumeExpenseCategory()">Cancel</button><button class="btn primary" onclick="createExpenseCategoryInline()">＋ Add Category</button></div>`);
}
function resumeExpenseCategory(){const p=state.expenseCategoryDraft;if(!p)return;closeModal();if(p.mode==='edit')editExpense(p.id,null,p.draft);else addExpense(p.draft);}

async function createExpenseCategoryInline(){
 const pending=state.expenseCategoryDraft;if(!pending)return;
 const name=document.getElementById('inlineExpenseCategory')?.value.trim();if(!name){toast('Enter a category name');return;}
 if((data.expenseCategories||[]).some(c=>c.name.trim().toLowerCase()===name.toLowerCase())){toast('This category already exists');return;}
 const {error}=await eazyApi.rpc('create_expense_category',{p_business_id:account.businessId,p_name:name});if(error){apiError('Unable to add category',error);return;}
 await loadBusinessData();const created=(data.expenseCategories||[]).find(c=>c.name.trim().toLowerCase()===name.toLowerCase());closeModal();state.expenseCategoryDraft=null;if(pending.mode==='edit')editExpense(pending.id,created?.id||null,pending.draft);else addExpense({...pending.draft,categoryId:created?.id||''});toast('Category added');
}
async function saveExpense(pending=null){
 const payload=pending||{n:document.getElementById('exName')?.value.trim(),a:Number(document.getElementById('exAmount')?.value||0),categoryId:document.getElementById('exCat')?.value||null,payment:document.getElementById('exPay')?.value||'cash',date:document.getElementById('exDate')?.value,notes:document.getElementById('exNotes')?.value.trim()||null};
 const {n,a,categoryId,payment,date,notes}=payload;
 if(!n||a<=0){toast('Enter expense description and amount');return}
 const openRegister=getOpenRegister();
 if(payment==='cash'&&!openRegister){state.pendingExpense=payload;openRegisterForExpense();return}
 const {data:result,error}=await eazyApi.rpc('create_expense',{p_business_id:account.businessId,p_category_id:categoryId,p_description:n,p_amount:a,p_payment_method:payment,p_expense_date:date?`${date}T00:00:00`:new Date().toISOString(),p_notes:notes,p_register_id:payment==='cash'?(openRegister?.id||null):null});
 if(error){apiError('Unable to save expense',error);return}
 state.pendingExpense=null;
 await loadBusinessData();closeModal();toast(`Expense saved · ${money(result?.amount||a)}`);render();
}
function openRegisterForExpense(){
 modal(`<div class="modal-head"><div><div class="eyebrow">Cash Register</div><h2>Open Register to Continue</h2></div><button class="close" onclick="closeModal()">×</button></div><div class="muted" style="margin-bottom:14px">A cash expense needs an open register. Open one to continue.</div><div class="field"><label>OPENING CASH</label><input class="input" id="expenseOpeningCash" type="number" min="0" value="10000"></div><div class="actions" style="margin-top:15px;justify-content:flex-end"><button class="btn" onclick="closeModal()">Cancel</button><button class="btn primary" onclick="confirmExpenseRegister()">Open Register</button></div>`)
}
async function confirmExpenseRegister(){
 const opening=Number(document.getElementById('expenseOpeningCash')?.value||0); if(opening<0){toast('Opening cash cannot be negative');return}
 const {error}=await eazyApi.post('/api/registers',{opening_cash:opening});
 if(error){apiError('Unable to open register',error);return}
 await loadBusinessData();closeModal();
 if(state.pendingExpense) await saveExpense(state.pendingExpense); else render();
}

