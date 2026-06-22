<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create Sales Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        .row { margin-bottom: 12px; }
        .items { margin-bottom: 12px; }
        .item { border: 1px solid #ddd; padding: 8px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>Create Sales Order</h1>

    <form method="post" action="{{ route('admin.sales_orders.store') }}">
        @csrf

        <div class="row">
            <label for="customer_id">Customer</label><br>
            <select id="customer_id" name="customer_id" required>
                <option value="">-- select customer --</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name ?? $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="items">
            <label>Items</label>
            <div id="items-container">
                <div class="item" data-index="0">
                    <div>
                        <label>SKU</label><br>
                        <select name="items[0][sku_code]" required>
                            <option value="">-- select SKU --</option>
                            @foreach ($skus as $s)
                                <option value="{{ $s->sku_code }}">{{ $s->sku_code }} @if($s->product) - {{ $s->product->name }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Quantity</label><br>
                        <input type="number" name="items[0][quantity]" value="1" min="1" required>
                    </div>
                    <div>
                        <label>Customization (JSON)</label><br>
                        <textarea name="items[0][customization_snapshot]" rows="3" cols="60"></textarea>
                    </div>
                    <button type="button" class="remove-item" onclick="removeItem(this)">Remove</button>
                </div>
            </div>
            <button type="button" id="add-item">Add item</button>
        </div>

        <div class="row">
            <label>Discount (minor)</label><br>
            <input type="number" name="discount_amount_minor" min="0">
        </div>

        <div class="row">
            <label>Shipping (minor)</label><br>
            <input type="number" name="shipping_amount_minor" min="0">
        </div>

        <div class="row">
            <label>Tax (minor)</label><br>
            <input type="number" name="tax_amount_minor" min="0">
        </div>

        <fieldset>
            <legend>Advance payment (optional)</legend>
            <div class="row">
                <label>Amount (minor)</label><br>
                <input type="number" name="advance_payment[amount_minor]" min="0">
            </div>
            <div class="row">
                <label>Due date</label><br>
                <input type="date" name="advance_payment[due_date]">
            </div>
        </fieldset>

        <div class="row">
            <button type="submit">Create Order</button>
        </div>
    </form>

    <script>
        let nextIndex = 1;

        document.getElementById('add-item').addEventListener('click', function () {
            const container = document.getElementById('items-container');
            const template = document.querySelector('.item');
            const clone = template.cloneNode(true);
            clone.dataset.index = nextIndex;

            // update input names inside clone
            clone.querySelectorAll('select, input, textarea').forEach(function (el) {
                if (el.name) {
                    el.name = el.name.replace(/items\[0\]/, 'items[' + nextIndex + ']');
                    if (el.type === 'number') el.value = (el.name.indexOf('quantity') !== -1) ? 1 : '';
                    if (el.tagName.toLowerCase() === 'textarea') el.value = '';
                }
            });

            container.appendChild(clone);
            nextIndex++;
        });

        function removeItem(button) {
            const item = button.closest('.item');
            const container = document.getElementById('items-container');
            if (container.querySelectorAll('.item').length > 1) {
                item.remove();
            }
        }
    </script>
</body>
</html>
