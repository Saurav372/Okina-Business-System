const hero = document.querySelector('.hero-product');
const heroImage = document.querySelector('[data-hero-image]');
const swatches = document.querySelectorAll('.swatch');
const menuButton = document.querySelector('.menu-button');
const mobileNav = document.querySelector('#mobile-nav');
const searchForm = document.querySelector('[data-search-form]');
const searchInput = document.querySelector('#product-search');
const searchStatus = document.querySelector('[data-search-status]');
const productCards = [...document.querySelectorAll('[data-product]')];
const emptyResults = document.querySelector('[data-empty-results]');

swatches.forEach((swatch) => {
  swatch.addEventListener('click', () => {
    swatches.forEach((item) => {
      item.classList.remove('is-active');
      item.setAttribute('aria-pressed', 'false');
    });
    swatch.classList.add('is-active');
    swatch.setAttribute('aria-pressed', 'true');
    hero.classList.add('is-changing');
    window.setTimeout(() => {
      heroImage.src = swatch.dataset.image;
      heroImage.alt = `${swatch.getAttribute('aria-label')} heavyweight cotton T-shirt`;
      hero.classList.remove('is-changing');
    }, 140);
  });
});

menuButton?.addEventListener('click', () => {
  const opening = menuButton.getAttribute('aria-expanded') !== 'true';
  menuButton.setAttribute('aria-expanded', String(opening));
  mobileNav.hidden = !opening;
});

document.querySelector('.search-trigger')?.addEventListener('click', () => searchInput?.focus());

function filterProducts(query) {
  const term = query.trim().toLowerCase();
  let visible = 0;
  productCards.forEach((card) => {
    const matched = !term || card.dataset.product.includes(term) || term.split(/\s+/).some((word) => card.dataset.product.includes(word));
    card.hidden = !matched;
    if (matched) visible += 1;
  });
  emptyResults.hidden = visible > 0;
  searchStatus.textContent = term ? `${visible} matching ${visible === 1 ? 'canvas' : 'canvases'} below` : '';
  document.querySelector('#products')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

searchForm?.addEventListener('submit', (event) => {
  event.preventDefault();
  filterProducts(searchInput.value);
});

document.querySelectorAll('[data-query]').forEach((button) => {
  button.addEventListener('click', () => {
    searchInput.value = button.dataset.query;
    filterProducts(button.dataset.query);
  });
});

document.querySelector('[data-clear-search]')?.addEventListener('click', () => {
  searchInput.value = '';
  filterProducts('');
  searchInput.focus();
});

