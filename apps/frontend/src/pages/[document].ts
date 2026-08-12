import type { APIRoute } from 'astro';
import { getCategories, getProducts, getStorefrontConfig } from '../lib/catalog';

const escapeXml = (value: string) => value.replace(/[<>&'\"]/g, (character) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', "'": '&apos;', '"': '&quot;' })[character] ?? character);

export const GET: APIRoute = async ({ params, site, url }) => {
  const origin = (site ?? new URL(url.origin)).toString().replace(/\/$/, '');

  if (params.document === 'robots.txt') {
    try {
      const config = await getStorefrontConfig();
      if (!config.seo.robots.index) {
        return new Response(['User-agent: *', 'Disallow: /', ''].join('\n'), { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
      }
    } catch {
      // A temporary settings outage must not unexpectedly de-index an otherwise healthy site.
    }
    return new Response(['User-agent: *', 'Allow: /', 'Disallow: /account', 'Disallow: /cart', 'Disallow: /checkout', 'Disallow: /order-confirmation', 'Disallow: /track-order', `Sitemap: ${origin}/sitemap.xml`, ''].join('\n'), { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
  }

  if (params.document === 'sitemap.xml') {
    let dynamicPaths: string[] = [];
    try {
      const [categories, products] = await Promise.all([getCategories(), getProducts()]);
      dynamicPaths = [...categories.map(({ slug }) => `/categories/${slug}`), ...products.map(({ slug }) => `/products/${slug}`)];
    } catch {
      // Core discovery pages remain discoverable during a temporary API outage.
    }
    const paths = ['/', '/categories', '/search', '/policies/shipping', '/policies/returns', '/policies/privacy', '/policies/terms', ...dynamicPaths];
    const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${paths.map((path) => `  <url><loc>${escapeXml(`${origin}${path}`)}</loc></url>`).join('\n')}\n</urlset>\n`;
    return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8', 'Cache-Control': 'public, max-age=900' } });
  }

  return new Response('Not found', { status: 404, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};
