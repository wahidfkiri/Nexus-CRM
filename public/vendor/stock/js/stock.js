'use strict';

window.Stock = {
  async loadStats(url){
    const {ok,data}=await Http.get(url);
    if(!ok||!data.data) return;
    const s=data.data;
    const set=(id,v)=>{const el=document.getElementById(id);if(el)el.textContent=v;};
    set('kpiArticles', s.articles_total ?? 0);
    set('kpiLowStock', s.articles_low_stock ?? 0);
    set('kpiSuppliers', s.suppliers_total ?? 0);
    set('kpiOrders', s.orders_total ?? 0);
  },

  initCrudTable(opts){
    const table = new CrmTable({
      tbodyId: opts.tbodyId,
      dataUrl: opts.dataUrl,
      perPage: 15,
      renderRow: opts.renderRow,
    });
    window._stockTable = table;
  },

  bindAjaxForm(formId){
    ajaxForm(formId);
  },

  addOrderLine(containerId){
    const tbody = document.getElementById(containerId);
    if(!tbody) return;
    const idx = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="items[${idx}][name]" class="form-control" required></td>
      <td><input type="number" name="items[${idx}][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
      <td><input type="text" name="items[${idx}][unit]" class="form-control" value="piece"></td>
      <td><input type="number" name="items[${idx}][unit_price]" class="form-control" min="0" step="any" value="0" required></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
  },

  fillOrderLineFromArticle(selectEl){
    const option = selectEl.selectedOptions[0];
    if(!option) return;
    const tr = selectEl.closest('tr');
    const nameInput = tr.querySelector('[name$="[name]"]');
    const unitInput = tr.querySelector('[name$="[unit]"]');
    const priceInput = tr.querySelector('[name$="[unit_price]"]');
    if(nameInput && !nameInput.value) nameInput.value = option.dataset.name || option.textContent;
    if(unitInput && option.dataset.unit) unitInput.value = option.dataset.unit;
    if(priceInput && option.dataset.price) priceInput.value = option.dataset.price;
  }
};
