const searchToggle = document.querySelector('[data-search-toggle]');
const searchPanel = document.querySelector('#search-panel');
const menuToggle = document.querySelector('.menu-toggle');
const mobileMenu = document.querySelector('#mobile-menu');
const toast = document.querySelector('[data-toast]');

searchToggle?.addEventListener('click', () => {
  const opening = searchToggle.getAttribute('aria-expanded') !== 'true';
  searchToggle.setAttribute('aria-expanded', String(opening));
  searchPanel.hidden = !opening;
  if (opening) searchPanel.querySelector('input')?.focus();
});

menuToggle?.addEventListener('click', () => {
  const opening = menuToggle.getAttribute('aria-expanded') !== 'true';
  menuToggle.setAttribute('aria-expanded', String(opening));
  mobileMenu.hidden = !opening;
});

function showToast(message) {
  if (!toast) return;
  toast.textContent = message;
  toast.hidden = false;
  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => { toast.hidden = true; }, 2800);
}

document.querySelectorAll('.quick-add').forEach((button) => {
  button.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    showToast('Choose a size on the product page.');
  });
});

const filterButtons = [...document.querySelectorAll('[data-filter]')];
const cards = [...document.querySelectorAll('.shop-card')];
const resultCount = document.querySelector('[data-result-count]');
const catalogEmpty = document.querySelector('[data-catalog-empty]');

function applyFilter(filter) {
  let visible = 0;
  cards.forEach((card) => {
    const show = filter === 'all' || card.dataset.category === filter;
    card.hidden = !show;
    if (show) visible += 1;
  });
  filterButtons.forEach((button) => {
    const selected = button.dataset.filter === filter;
    button.classList.toggle('is-active', selected);
    button.setAttribute('aria-pressed', String(selected));
  });
  if (resultCount) resultCount.textContent = `${visible} ${visible === 1 ? 'product' : 'products'}`;
  if (catalogEmpty) catalogEmpty.hidden = visible > 0;
}

filterButtons.forEach((button) => button.addEventListener('click', () => applyFilter(button.dataset.filter)));
document.querySelector('[data-show-all]')?.addEventListener('click', () => applyFilter('all'));

const filterDrawer = document.querySelector('[data-filter-drawer]');
document.querySelector('[data-filter-panel]')?.addEventListener('click', () => { filterDrawer.hidden = false; });
document.querySelector('[data-close-filter]')?.addEventListener('click', () => { filterDrawer.hidden = true; });

const colourButtons = [...document.querySelectorAll('[data-colour]')];
const productImage = document.querySelector('[data-product-image]');
const detailImage = document.querySelector('[data-detail-image]');
const lookImage = document.querySelector('[data-look-image]');
const lookColour = document.querySelector('[data-look-colour]');
const colourName = document.querySelector('[data-colour-name]');
colourButtons.forEach((button) => {
  button.addEventListener('click', () => {
    colourButtons.forEach((item) => { item.classList.remove('is-selected'); item.setAttribute('aria-pressed', 'false'); });
    button.classList.add('is-selected');
    button.setAttribute('aria-pressed', 'true');
    productImage.src = button.dataset.image;
    productImage.alt = `${button.dataset.colour} Studio Heavy Tee, front view`;
    detailImage.src = button.dataset.image;
    detailImage.alt = `${button.dataset.colour} Studio Heavy Tee fabric and collar detail`;
    lookImage.src = button.dataset.image;
    lookImage.alt = `${button.dataset.colour} Studio Heavy Tee thumbnail`;
    lookColour.textContent = `${button.dataset.colour} / ₹499`;
    colourName.textContent = button.dataset.colour;
  });
});

const sizeButtons = [...document.querySelectorAll('.size-choice button:not(:disabled)')];
const addButton = document.querySelector('[data-add-to-bag]');
const sizeError = document.querySelector('[data-size-error]');
let selectedSize = '';
sizeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    sizeButtons.forEach((item) => { item.classList.remove('is-selected'); item.setAttribute('aria-pressed', 'false'); });
    selectedSize = button.textContent.trim();
    button.classList.add('is-selected');
    button.setAttribute('aria-pressed', 'true');
    addButton.textContent = `Customize ${selectedSize} · ₹499`;
    addButton.classList.add('is-ready');
    sizeError.textContent = '';
  });
});

addButton?.addEventListener('click', () => {
  if (!selectedSize) { sizeError.textContent = 'Choose an available size before continuing.'; return; }
  document.querySelector('[data-bag-count]').textContent = '1';
  document.querySelector('[data-bag-link]').setAttribute('aria-label', 'Shopping bag with 1 item');
  showToast(`Studio Heavy Tee, size ${selectedSize}, added to your bag.`);
});

document.querySelector('.look-card > button')?.addEventListener('click', (event) => { event.currentTarget.closest('.look-card').hidden = true; });
