type ApiResponse<T> = { data: T; guidance?: Record<string, unknown> };

export class CatalogRequestError extends Error {
  constructor(message: string, public readonly status: number, public readonly path: string) {
    super(message);
    this.name = 'CatalogRequestError';
  }
}

export type CatalogCategory = {
  slug: string; name: string; description: string | null; seo_title: string | null;
  seo_description: string | null; sort_order: number; published_at: string | null; products_count: number;
};

export type CatalogProductSku = {
  sku_code: string; variant_key: string; option_values: Array<{ code: string; label: string }>;
  name_suffix: string | null; status: string; direct_checkout_enabled: boolean; quote_required: boolean;
  track_stock: boolean; allow_backorder: boolean; price_minor: number; compare_at_price_minor: number | null;
  weight_grams: number | null; dimensions_mm: { length: number | null; width: number | null; height: number | null };
  sort_order: number; availability: { available_for_checkout: boolean; requires_quote: boolean; is_in_stock: boolean; is_low_stock: boolean };
};

export type CatalogMedia = {
  public_id: string; role: string; alt_text: string | null; sort_order: number; url: string;
  mime_type: string; width: number | null; height: number | null;
};

export type CatalogSeo = {
  title: string; description: string | null; canonical_url: string | null;
  robots: { index: boolean; follow: boolean };
  open_graph: { title: string; description: string | null; image: CatalogMedia | null };
  twitter: { title: string; description: string | null; image: CatalogMedia | null };
};

export type CatalogProduct = {
  slug: string; name: string; short_description: string | null; description: string | null;
  product_type: string; customization_mode: string; fulfillment_type: string; status: string; visibility: string;
  direct_checkout_enabled: boolean; quote_enabled: boolean; min_order_quantity: number; max_order_quantity: number | null;
  bulk_threshold_quantity: number | null; base_price_minor: number; currency: string; seo_title: string | null;
  seo_description: string | null; sort_order: number; published_at: string | null;
  seo: CatalogSeo; cover_image: CatalogMedia | null; media: CatalogMedia[];
  category: { slug: string; name: string; seo_title: string | null } | null;
  variants: Array<{ name: string; code: string; display_type: string; values: unknown[]; is_required: boolean; sort_order: number }>;
  skus: CatalogProductSku[];
};

export type StorefrontConfig = {
  business: {
    company_name: string; support_email: string | null; support_phone: string | null;
    default_currency: string; tax_inclusive_pricing: boolean;
  };
  checkout: { online_payments_enabled: boolean; cod_enabled: boolean; default_gateway: string };
  seo: {
    site_title: string; meta_description: string | null;
    robots: { index: boolean; follow: boolean }; open_graph_image: string | null;
  };
};

export const defaultStorefrontConfig: StorefrontConfig = {
  business: { company_name: 'Okina Craft', support_email: null, support_phone: null, default_currency: 'INR', tax_inclusive_pricing: false },
  checkout: { online_payments_enabled: true, cod_enabled: false, default_gateway: 'cashfree' },
  seo: { site_title: 'Okina Craft', meta_description: null, robots: { index: true, follow: true }, open_graph_image: null },
};

export type CustomizationOptions = {
  product: { slug: string; name: string; short_description: string | null; description: string | null; customization_mode: string; status: string; visibility: string; currency: string; category: { slug: string; name: string } | null };
  option_groups: Array<{ name: string; code: string; display_type: string; is_required: boolean; sort_order: number; values: Array<{ code: string; label: string; sort_order: number; is_active: boolean }> }>;
  size_options: Array<{ code: string; label: string; sort_order: number; is_active: boolean }>;
  print_positions: Array<{ code: string; label: string }>;
  print_methods: Array<{ code: string; label: string }>;
  skus: Array<{ sku_code: string; variant_key: string; option_values: Array<{ code: string; label: string }>; name_suffix: string | null; status: string; direct_checkout_enabled: boolean; quote_required: boolean; price_minor: number; availability: { available_for_checkout: boolean; requires_quote: boolean } }>;
  validation: { requires_product_sku_match: boolean; requires_print_position: boolean; requires_print_method: boolean; print_method_compatibility: Record<string, string[]> };
};

const apiBaseUrl = normalizeBaseUrl(import.meta.env.PUBLIC_API_BASE_URL ?? 'http://127.0.0.1:8000/api');
function normalizeBaseUrl(value: string): string { return value.endsWith('/') ? value.slice(0, -1) : value; }
function endpoint(path: string): string { return `${apiBaseUrl}/${path.replace(/^\/+/, '')}`; }

async function getJson<T>(path: string): Promise<T> {
  let response: Response;
  try { response = await fetch(endpoint(path), { headers: { Accept: 'application/json' } }); }
  catch { throw new CatalogRequestError('The catalog is temporarily unavailable.', 0, path); }
  if (!response.ok) throw new CatalogRequestError(response.status === 404 ? 'The requested catalog item was not found.' : 'The catalog is temporarily unavailable.', response.status, path);
  return response.json() as Promise<T>;
}

export async function getCatalogHomeData(): Promise<{ categories: CatalogCategory[]; products: CatalogProduct[] }> {
  const [categoryResponse, productResponse] = await Promise.all([getJson<ApiResponse<CatalogCategory[]>>('/catalog/categories'), getJson<ApiResponse<CatalogProduct[]>>('/catalog/products')]);
  return { categories: categoryResponse.data, products: productResponse.data };
}
export async function getStorefrontConfig(): Promise<StorefrontConfig> { return (await getJson<ApiResponse<StorefrontConfig>>('/catalog/storefront')).data; }
export async function getCategories(): Promise<CatalogCategory[]> { return (await getJson<ApiResponse<CatalogCategory[]>>('/catalog/categories')).data; }
export async function getCategorySlugs(): Promise<string[]> { return (await getCategories()).map(({ slug }) => slug); }
export async function getCategoryPageData(slug: string): Promise<{ category: CatalogCategory | null; products: CatalogProduct[] }> { return (await getJson<ApiResponse<{ category: CatalogCategory | null; products: CatalogProduct[] }>>(`/catalog/categories/${slug}/products`)).data; }
export async function getProducts(): Promise<CatalogProduct[]> { return (await getJson<ApiResponse<CatalogProduct[]>>('/catalog/products')).data; }
export async function getProductSlugs(): Promise<string[]> { return (await getProducts()).map(({ slug }) => slug); }
export async function getProductPageData(slug: string): Promise<CatalogProduct | null> { return (await getJson<ApiResponse<CatalogProduct | null>>(`/catalog/products/${slug}`)).data; }
export async function getCustomizationOptions(slug: string): Promise<CustomizationOptions | null> { return (await getJson<ApiResponse<CustomizationOptions | null>>(`/catalog/products/${slug}/customization-options`)).data; }

export function formatMoney(minor: number, currency: string): string { return new Intl.NumberFormat('en-IN', { style: 'currency', currency, maximumFractionDigits: 0 }).format(minor / 100); }
export function formatDate(value: string | null): string | null { return value ? new Intl.DateTimeFormat('en-IN', { dateStyle: 'medium' }).format(new Date(value)) : null; }
export function titleCase(value: string): string { return value.split(/[-_]/g).map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' '); }
