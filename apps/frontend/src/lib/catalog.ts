type ApiResponse<T> = {
  data: T;
  guidance?: Record<string, unknown>;
};

export type CatalogCategory = {
  slug: string;
  name: string;
  description: string | null;
  seo_title: string | null;
  seo_description: string | null;
  sort_order: number;
  published_at: string | null;
  products_count: number;
};

export type CatalogProductSku = {
  sku_code: string;
  variant_key: string;
  option_values: Array<{ code: string; label: string }>;
  name_suffix: string | null;
  status: string;
  direct_checkout_enabled: boolean;
  quote_required: boolean;
  track_stock: boolean;
  allow_backorder: boolean;
  price_minor: number;
  compare_at_price_minor: number | null;
  weight_grams: number | null;
  dimensions_mm: {
    length: number | null;
    width: number | null;
    height: number | null;
  };
  sort_order: number;
  availability: {
    available_for_checkout: boolean;
    requires_quote: boolean;
    is_in_stock: boolean;
    is_low_stock: boolean;
  };
};

export type CatalogProduct = {
  slug: string;
  name: string;
  short_description: string | null;
  description: string | null;
  product_type: string;
  customization_mode: string;
  fulfillment_type: string;
  status: string;
  visibility: string;
  direct_checkout_enabled: boolean;
  quote_enabled: boolean;
  min_order_quantity: number;
  max_order_quantity: number | null;
  bulk_threshold_quantity: number | null;
  base_price_minor: number;
  currency: string;
  seo_title: string | null;
  seo_description: string | null;
  sort_order: number;
  published_at: string | null;
  category: {
    slug: string;
    name: string;
    seo_title: string | null;
  } | null;
  variants: Array<{
    name: string;
    code: string;
    display_type: string;
    values: unknown[];
    is_required: boolean;
    sort_order: number;
  }>;
  skus: CatalogProductSku[];
};

const apiBaseUrl = normalizeBaseUrl(
  import.meta.env.PUBLIC_API_BASE_URL ?? 'http://127.0.0.1:8000/api',
);

function normalizeBaseUrl(value: string): string {
  return value.endsWith('/') ? value.slice(0, -1) : value;
}

function endpoint(path: string): string {
  return `${apiBaseUrl}/${path.replace(/^\/+/, '')}`;
}

async function getJson<T>(path: string): Promise<T> {
  const response = await fetch(endpoint(path));

  if (!response.ok) {
    throw new Error(`Catalog request failed for ${path}: ${response.status} ${response.statusText}`);
  }

  return response.json() as Promise<T>;
}

export async function getCatalogHomeData(): Promise<{
  categories: CatalogCategory[];
  products: CatalogProduct[];
}> {
  const [categoriesResponse, productsResponse] = await Promise.all([
    getJson<ApiResponse<CatalogCategory[]>>('/catalog/categories'),
    getJson<ApiResponse<CatalogProduct[]>>('/catalog/products'),
  ]);

  return {
    categories: categoriesResponse.data,
    products: productsResponse.data,
  };
}

export async function getCategories(): Promise<CatalogCategory[]> {
  const response = await getJson<ApiResponse<CatalogCategory[]>>('/catalog/categories');
  return response.data;
}

export async function getCategorySlugs(): Promise<string[]> {
  return (await getCategories()).map((category) => category.slug);
}

export async function getCategoryPageData(slug: string): Promise<{
  category: CatalogCategory | null;
  products: CatalogProduct[];
}> {
  const response = await getJson<ApiResponse<{ category: CatalogCategory | null; products: CatalogProduct[] }>>(
    `/catalog/categories/${slug}/products`,
  );

  return response.data;
}

export async function getProducts(): Promise<CatalogProduct[]> {
  const response = await getJson<ApiResponse<CatalogProduct[]>>('/catalog/products');
  return response.data;
}

export async function getProductSlugs(): Promise<string[]> {
  return (await getProducts()).map((product) => product.slug);
}

export async function getProductPageData(slug: string): Promise<CatalogProduct | null> {
  const response = await getJson<ApiResponse<CatalogProduct | null>>(`/catalog/products/${slug}`);
  return response.data;
}

export function formatMoney(minor: number, currency: string): string {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency,
    maximumFractionDigits: 0,
  }).format(minor / 100);
}

export function formatDate(value: string | null): string | null {
  if (!value) {
    return null;
  }

  return new Intl.DateTimeFormat('en-IN', {
    dateStyle: 'medium',
  }).format(new Date(value));
}

export function titleCase(value: string): string {
  return value
    .split(/[-_]/g)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}
