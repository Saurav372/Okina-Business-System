const qs = (selector, root = document) => root.querySelector(selector);
const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
const money = (value) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(value);

const toast = qs('[data-toast]');
function showToast(message) {
  if (!toast) return;
  toast.textContent = message;
  toast.hidden = false;
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => { toast.hidden = true; }, 2600);
}

const menuTrigger = qs('[data-menu-trigger]');
const mobileMenu = qs('#mobile-menu');
menuTrigger?.addEventListener('click', () => {
  const open = menuTrigger.getAttribute('aria-expanded') !== 'true';
  menuTrigger.setAttribute('aria-expanded', String(open));
  mobileMenu.hidden = !open;
});

qsa('[data-heart]').forEach((button) => button.addEventListener('click', (event) => {
  event.preventDefault();
  button.classList.toggle('is-saved');
  button.setAttribute('aria-pressed', String(button.classList.contains('is-saved')));
  showToast(button.classList.contains('is-saved') ? 'Saved to your favourites.' : 'Removed from favourites.');
}));

const catalogCards = qsa('[data-catalog-product]');
const catalogCount = qs('[data-catalog-count]');
const catalogEmpty = qs('[data-catalog-empty]');
function filterCatalog(term) {
  const normalized = String(term || '').trim().toLowerCase();
  let count = 0;
  catalogCards.forEach((card) => {
    const visible = !normalized || card.dataset.catalogProduct.includes(normalized) || normalized.split(/\s+/).some((word) => card.dataset.catalogProduct.includes(word));
    card.hidden = !visible;
    if (visible) count += 1;
  });
  if (catalogCount) catalogCount.textContent = `${count} ${count === 1 ? 'style' : 'styles'}`;
  if (catalogEmpty) catalogEmpty.hidden = count > 0;
}

qsa('[data-catalog-filter]').forEach((button) => button.addEventListener('click', () => {
  qsa('[data-catalog-filter]').forEach((item) => item.classList.remove('active'));
  button.classList.add('active');
  filterCatalog(button.dataset.catalogFilter === 'all' ? '' : button.dataset.catalogFilter);
}));
qs('[data-show-all]')?.addEventListener('click', () => {
  const input = qs('.global-search input');
  if (input) input.value = '';
  filterCatalog('');
  qsa('[data-catalog-filter]').forEach((item) => item.classList.toggle('active', item.dataset.catalogFilter === 'all'));
});

qsa('[data-search-form]').forEach((form) => form.addEventListener('submit', (event) => {
  event.preventDefault();
  const input = qs('input[type="search"]', form);
  if (document.body.classList.contains('catalog-page')) filterCatalog(input.value);
  else window.location.href = `shop.html?q=${encodeURIComponent(input.value)}`;
}));

if (document.body.classList.contains('catalog-page')) {
  const initialQuery = new URLSearchParams(location.search).get('q') || '';
  const searchInput = qs('.global-search input');
  if (initialQuery && searchInput) { searchInput.value = initialQuery; filterCatalog(initialQuery); }
}

const productImage = qs('[data-product-image]');
const selectedColour = qs('[data-colour-label]');
qsa('[data-colour]').forEach((button) => button.addEventListener('click', () => {
  qsa('[data-colour]').forEach((item) => { item.classList.remove('selected'); item.setAttribute('aria-pressed','false'); });
  button.classList.add('selected');
  button.setAttribute('aria-pressed','true');
  if (productImage) { productImage.src = button.dataset.image; productImage.alt = `${button.dataset.colour} Studio Heavy Tee`; }
  if (selectedColour) selectedColour.textContent = button.dataset.colour;
}));

let selectedSize = '';
qsa('[data-size]').forEach((button) => button.addEventListener('click', () => {
  qsa('[data-size]').forEach((item) => { item.classList.remove('selected'); item.setAttribute('aria-pressed','false'); });
  selectedSize = button.dataset.size;
  button.classList.add('selected');
  button.setAttribute('aria-pressed','true');
  const error = qs('[data-size-error]');
  if (error) error.textContent = '';
}));

qs('[data-start-customizing]')?.addEventListener('click', (event) => {
  if (!selectedSize) { event.preventDefault(); qs('[data-size-error]').textContent = 'Choose an available size to start customizing.'; }
  else sessionStorage.setItem('okina_selected_size', selectedSize);
});

const builder = qs('[data-builder]');
if (builder) {
  let step = 1;
  const state = { method: 'upload', file: '', placement: 'Front centre', print: 'DTF', quantities: { XS:0,S:2,M:4,L:4,XL:2 }, unit: 499 };
  const updateBuilder = () => {
    qsa('[data-builder-step]').forEach((panel) => { panel.hidden = Number(panel.dataset.builderStep) !== step; });
    qsa('[data-progress]').forEach((bar, index) => { bar.classList.toggle('done', index + 1 < step); bar.classList.toggle('active', index + 1 === step); });
    qs('[data-step-count]').textContent = `Step ${step} of 4`;
    qs('[data-builder-back]').hidden = step === 1;
    qs('[data-builder-next]').textContent = step === 4 ? (quantities() >= 25 ? 'Send to quotation team' : 'Add configured item to bag') : 'Continue';
    if (step === 4) renderReview();
  };
  const quantities = () => qsa('[data-size-qty]').reduce((sum,input) => sum + Number(input.value || 0),0);
  const updatePrice = () => {
    const qty = quantities();
    const unit = qty >= 25 ? 419 : qty >= 10 ? 449 : 499;
    state.unit = unit;
    qs('[data-total-qty]').textContent = String(qty);
    qs('[data-unit-price]').textContent = money(unit);
    qs('[data-builder-total]').textContent = money(qty * unit);
    qs('[data-bulk-notice]').hidden = qty < 25;
  };
  qsa('[name="method"]').forEach((input) => input.addEventListener('change', () => { state.method = input.value; qs('[data-upload-block]').hidden = input.value !== 'upload'; }));
  qs('[data-artwork-file]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    const status = qs('[data-upload-state]');
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) { event.target.value = ''; status.hidden = false; status.style.background = '#fff1f0'; status.style.color = '#b42318'; status.textContent = 'That file is over 5 MB. Choose a smaller PNG, JPG, or PDF and try again.'; return; }
    state.file = file.name;
    status.hidden = false; status.style.background = ''; status.style.color = ''; status.textContent = `✓ ${file.name} uploaded securely. You can replace it before checkout.`;
    qs('[data-artwork-preview]').textContent = 'YOUR\nARTWORK';
  });
  qsa('[name="placement"]').forEach((input) => input.addEventListener('change', () => { state.placement = input.value; }));
  qs('[data-print-method]')?.addEventListener('change', (event) => { state.print = event.target.value; });
  qsa('[data-size-qty]').forEach((input) => input.addEventListener('input', updatePrice));
  const renderReview = () => {
    const sizeText = qsa('[data-size-qty]').filter((input) => Number(input.value)>0).map((input) => `${input.dataset.sizeQty} × ${input.value}`).join(' · ');
    qs('[data-review-method]').textContent = state.method === 'upload' ? `Uploaded artwork${state.file ? ` · ${state.file}` : ''}` : state.method === 'text' ? 'Text customization' : 'Okina design help';
    qs('[data-review-placement]').textContent = `${state.placement} · ${state.print}`;
    qs('[data-review-sizes]').textContent = sizeText || 'No quantities selected';
    qs('[data-review-total]').textContent = `${quantities()} pieces · ${money(quantities()*state.unit)}`;
  };
  qs('[data-builder-next]').addEventListener('click', () => {
    const message = qs('[data-builder-error]'); message.textContent = '';
    if (step === 1 && state.method === 'upload' && !state.file) { message.textContent = 'Upload an artwork file, or choose text customization or design help.'; return; }
    if (step === 3 && quantities() < 1) { message.textContent = 'Add at least one item across the size range.'; return; }
    if (step < 4) { step += 1; updateBuilder(); window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
    localStorage.setItem('okina_design03_cart', JSON.stringify({ name:'Studio Heavy Tee', colour:'Black', method:state.method, file:state.file, placement:state.placement, print:state.print, qty:quantities(), unit:state.unit, total:quantities()*state.unit }));
    window.location.href = quantities() >= 25 ? 'quote.html' : 'cart.html';
  });
  qs('[data-builder-back]').addEventListener('click', () => { if (step>1) { step -= 1; updateBuilder(); } });
  updatePrice(); updateBuilder();
}

const cartItem = qs('[data-cart-item]');
if (cartItem) {
  const saved = JSON.parse(localStorage.getItem('okina_design03_cart') || 'null');
  let qty = saved?.qty || 12;
  const unit = saved?.unit || 449;
  const renderCart = () => {
    qs('[data-cart-qty]').textContent = qty;
    qs('[data-cart-line]').textContent = money(qty * unit);
    qs('[data-cart-subtotal]').textContent = money(qty * unit);
    qs('[data-cart-total]').textContent = money(qty * unit);
    qsa('[data-bag-count]').forEach((el) => { el.textContent = '1'; });
  };
  qs('[data-qty-minus]').addEventListener('click', () => { qty = Math.max(1,qty-1); renderCart(); });
  qs('[data-qty-plus]').addEventListener('click', () => { qty += 1; renderCart(); });
  qs('[data-remove-cart]').addEventListener('click', () => { cartItem.hidden = true; qs('[data-cart-layout]').hidden = true; qs('[data-cart-empty]').hidden = false; qsa('[data-bag-count]').forEach((el) => { el.textContent = '0'; }); });
  qs('[data-restore-cart]')?.addEventListener('click', () => { cartItem.hidden = false; qs('[data-cart-layout]').hidden = false; qs('[data-cart-empty]').hidden = true; renderCart(); });
  renderCart();
}

const checkoutForm = qs('[data-checkout-form]');
checkoutForm?.addEventListener('submit', (event) => {
  event.preventDefault();
  qsa('[data-field-error]').forEach((el) => { el.textContent = ''; });
  const required = [['name','Enter the recipient name.'],['phone','Enter a 10-digit phone number.'],['address','Enter the delivery address.'],['city','Enter the city.'],['postal','Enter a valid 6-digit PIN code.']];
  let firstInvalid = null;
  required.forEach(([name,message]) => {
    const input = checkoutForm.elements[name];
    const invalid = !input.value.trim() || (name === 'phone' && !/^\d{10}$/.test(input.value.trim())) || (name === 'postal' && !/^\d{6}$/.test(input.value.trim()));
    if (invalid) { qs(`[data-error-for="${name}"]`).textContent = message; input.setAttribute('aria-invalid','true'); firstInvalid ||= input; }
    else input.removeAttribute('aria-invalid');
  });
  const alert = qs('[data-form-alert]');
  if (firstInvalid) { alert.hidden = false; alert.textContent = 'Review the highlighted delivery details. Your other information has been preserved.'; firstInvalid.focus(); return; }
  alert.hidden = true;
  sessionStorage.setItem('okina_checkout_name', checkoutForm.elements.name.value.trim());
  const submit = checkoutForm.querySelector('button[type="submit"]');
  submit.disabled = true;
  submit.setAttribute('aria-busy','true');
  submit.textContent = 'Securing payment…';
  setTimeout(() => { window.location.href = 'confirmation.html'; }, 650);
});

qs('[data-proof-approve]')?.addEventListener('click', () => {
  qs('[data-proof-card]').innerHTML = '<h2>Proof approved</h2><p>Your approval was recorded today at 4:28 PM. Production can now begin.</p><span class="status-pill">Approved</span>';
  showToast('Proof V1 approved. Production has been notified.');
});
qs('[data-proof-changes]')?.addEventListener('click', () => showToast('Change request opened. Your current proof remains available.'));
