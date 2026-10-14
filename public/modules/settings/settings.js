(function(){
  'use strict';
  const tabs=[...document.querySelectorAll('.settings-tab')];
  const panels=[...document.querySelectorAll('.settings-panel')];
  function activate(id){tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===id));panels.forEach(p=>p.classList.toggle('active',p.id===id));history.replaceState(null,'','#'+id)}
  tabs.forEach(t=>t.addEventListener('click',()=>activate(t.dataset.tab)));
  if(location.hash==='#receipt-settings') activate('receipt-settings');

  const modal=document.getElementById('menu-modal'), form=document.getElementById('menu-form'), title=document.getElementById('menu-modal-title'), method=document.getElementById('method-slot');
  let menuMode='add';
  let originalActive=true;
  function openAdd(){menuMode='add';originalActive=true;form.reset();form.action=window.SETTINGS_MENU_STORE_URL;method.innerHTML='';title.textContent='Add Menu Item';document.getElementById('menu-active').checked=true;modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.getElementById('menu-name').focus()}
  function openEdit(item){menuMode='edit';form.reset();form.action=window.SETTINGS_MENU_STORE_URL+'/'+item.id;method.innerHTML='<input type="hidden" name="_method" value="PUT">';title.textContent='Edit Menu Item';document.getElementById('menu-name').value=item.name||'';document.getElementById('menu-category').value=item.category||'';document.getElementById('menu-price').value=item.price||'';document.getElementById('menu-discount').value=item.discounted_price||'';originalActive=Number(item.is_active)!==0;document.getElementById('menu-active').checked=originalActive;modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.getElementById('menu-name').focus()}
  function close(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true')}
  document.getElementById('add-menu-btn')?.addEventListener('click',openAdd);
  document.querySelectorAll('.edit-menu-btn').forEach(b=>b.addEventListener('click',()=>openEdit(JSON.parse(b.dataset.item))));
  document.getElementById('menu-modal-close')?.addEventListener('click',close);
  document.getElementById('menu-cancel')?.addEventListener('click',close);
  modal?.addEventListener('click',e=>{if(e.target===modal)close()});

  // Custom confirmation dialog shared by all Settings actions.
  const confirmModal=document.getElementById('confirm-modal');
  const confirmDialog=document.getElementById('confirm-dialog');
  const confirmTitle=document.getElementById('confirm-title');
  const confirmMessage=document.getElementById('confirm-message');
  const confirmSummary=document.getElementById('confirm-summary');
  const confirmIcon=document.getElementById('confirm-icon');
  const confirmCancel=document.getElementById('confirm-cancel');
  const confirmProceed=document.getElementById('confirm-proceed');
  let pendingAction=null;
  let returnFocus=null;

  function askConfirm(options, action){
    returnFocus=document.activeElement;
    pendingAction=action;
    confirmTitle.textContent=options.title||'Confirm changes';
    confirmMessage.textContent=options.message||'Please confirm before continuing.';
    confirmSummary.textContent=options.summary||'';
    confirmProceed.textContent=options.confirmText||'Confirm';
    confirmIcon.textContent=options.danger?'!':'✓';
    confirmDialog.classList.toggle('is-danger',!!options.danger);
    confirmModal.classList.add('open');
    confirmModal.setAttribute('aria-hidden','false');
    document.body.classList.add('confirm-open');
    requestAnimationFrame(()=>confirmCancel.focus());
  }
  function closeConfirm(){
    pendingAction=null;
    confirmModal.classList.remove('open');
    confirmModal.setAttribute('aria-hidden','true');
    document.body.classList.remove('confirm-open');
    returnFocus?.focus?.();
  }
  confirmCancel?.addEventListener('click',closeConfirm);
  confirmProceed?.addEventListener('click',()=>{
    const action=pendingAction;
    pendingAction=null;
    confirmModal.classList.remove('open');
    confirmModal.setAttribute('aria-hidden','true');
    document.body.classList.remove('confirm-open');
    if(action) action();
  });
  confirmModal?.addEventListener('click',e=>{if(e.target===confirmModal)closeConfirm()});

  form?.addEventListener('submit',e=>{
    e.preventDefault();
    if(!form.reportValidity()) return;
    const name=document.getElementById('menu-name').value.trim()||'this menu item';
    const isActive=document.getElementById('menu-active').checked;
    const availabilityChanged=menuMode==='edit' && isActive!==originalActive;
    let message=menuMode==='add'?'This item will be added to the cashier menu.':'Your changes will be applied to this menu item.';
    if(availabilityChanged) message += isActive?' It will also become available on the cashier menu.':' It will also be hidden from the cashier menu.';
    askConfirm({
      title:menuMode==='add'?'Add menu item?':'Save menu changes?',
      message,
      summary:name,
      confirmText:menuMode==='add'?'Add Menu Item':'Save Changes'
    },()=>form.submit());
  });

  document.querySelectorAll('.delete-menu-form').forEach(f=>f.addEventListener('submit',e=>{
    e.preventDefault();
    const name=f.dataset.itemName||'this menu item';
    askConfirm({
      title:'Delete menu item?',
      message:'This item will be removed from the menu. Existing order history will stay unchanged.',
      summary:name,
      confirmText:'Delete Menu Item',
      danger:true
    },()=>f.submit());
  }));

  document.getElementById('receipt-settings-form')?.addEventListener('submit',function(e){
    e.preventDefault();
    if(!this.reportValidity()) return;
    askConfirm({
      title:'Save receipt settings?',
      message:'The updated store details and receipt options will be used for future receipts.',
      summary:'Receipt Settings',
      confirmText:'Save Settings'
    },()=>this.submit());
  });

  document.querySelector('.logo-upload-form')?.addEventListener('submit',function(e){
    e.preventDefault();
    if(!this.reportValidity()) return;
    const file=this.querySelector('input[type="file"]')?.files?.[0];
    askConfirm({
      title:'Change store logo?',
      message:'The selected image will replace the logo used in receipt settings.',
      summary:file?.name||'New store logo',
      confirmText:'Upload Logo'
    },()=>this.submit());
  });

  document.addEventListener('keydown',e=>{
    if(e.key!=='Escape') return;
    if(confirmModal?.classList.contains('open')) closeConfirm();
    else close();
  });

  function clock(){const n=new Date(),d=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],m=['January','February','March','April','May','June','July','August','September','October','November','December'];let h=n.getHours(),ap=h>=12?'PM':'AM';h=h%12||12;document.getElementById('current-day').textContent=d[n.getDay()];document.getElementById('current-date').textContent=`${m[n.getMonth()]} ${n.getDate()}, ${n.getFullYear()} at ${h}:${String(n.getMinutes()).padStart(2,'0')} ${ap}`}
  clock();setInterval(clock,1000);
  const pb=document.getElementById('profile-btn'),pd=document.getElementById('profile-dropdown');pb?.addEventListener('click',e=>{e.stopPropagation();pd?.classList.toggle('open')});document.addEventListener('click',()=>pd?.classList.remove('open'));document.getElementById('logout-btn')?.addEventListener('click',e=>location.href=e.currentTarget.dataset.logoutUrl);
})();
