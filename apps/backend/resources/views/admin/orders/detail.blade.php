<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Detail | Okina Craft</title>
    <style>
        :root { --panel: #ffffff; --text: #0f172a; --muted:#475569; --line:#e6edf0; }
        body { font-family: Arial, sans-serif; margin:0; padding:0; color:var(--text); background:#f6fbfb; }
        header, main { width:min(100%,1100px); margin:0 auto; padding:20px; }
        .panel { background:var(--panel); border:1px solid var(--line); padding:20px; border-radius:12px; }
        .items { display:flex; flex-wrap:wrap; gap:16px; margin-top:12px; }
        .item { width:260px; border:1px solid var(--line); border-radius:10px; padding:12px; background:#fff; }
        .thumb { width:100%; height:160px; object-fit:cover; border-radius:8px; background:#f3f6f7; display:block; }
        .meta { margin-top:8px; font-size:14px; color:var(--muted); }
    </style>
</head>
<body>
    <header>
        <h1>Order detail — {{ $order->public_id }}</h1>
    </header>

    <main>
        <div class="panel">
            <h2>Items</h2>
            <div class="items">
                @foreach ($summary['items'] as $item)
                    <div class="item">
                        @php $cs = $item['customization_snapshot'] ?? null; @endphp

                        @if (is_array($cs) && array_key_exists('mockup_preview_url', $cs) && $cs['mockup_preview_url'])
                            <img class="thumb" src="{{ $cs['mockup_preview_url'] }}" alt="Preview">
                        @else
                            <div class="thumb" style="display:flex;align-items:center;justify-content:center;color:var(--muted);">No preview</div>
                        @endif

                        <div class="meta">
                            <div><strong>{{ $item['product_name'] ?? ($item['product_name_snapshot'] ?? 'Product') }}</strong></div>
                            <div>SKU: {{ $item['sku_code'] ?? ($item['sku_code_snapshot'] ?? '') }}</div>
                            <div>Quantity: {{ $item['quantity'] ?? 1 }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </main>
</body>
</html>
