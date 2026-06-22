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

    <form id="sales-order-form" method="post" action="{{ route('admin.sales_orders.store') }}">
        @csrf

        <div id="form-errors" style="color: #b00020; margin-bottom: 12px;"></div>

        <div class="row">
            <label for="customer_id">Customer</label><br>
            <select id="customer_id" name="customer_id" required>
                <option value="">-- select customer --</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name ?? $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <label for="sku-filter">Filter SKUs</label><br>
            <input id="sku-filter" placeholder="Type to filter SKUs across item selects" style="width: 60%;">
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
                                <option value="{{ $s->sku_code }}">{{ $s->sku_code }}@if($s->product) - {{ $s->product->name }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Quantity</label><br>
                        <input type="number" name="items[0][quantity]" value="1" min="1" required>
                    </div>
                    <div>
                        <label>Customization (JSON)</label><br>
                        <textarea name="items[0][customization_snapshot]" rows="3" cols="60" placeholder='e.g. [] or {"placement":{}}'></textarea>
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
            <button type="submit" id="submit-btn">Create Order</button>
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

        // SKU filter across all item selects
        document.getElementById('sku-filter').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase().trim();
            document.querySelectorAll('#items-container select').forEach(function (sel) {
                sel.querySelectorAll('option').forEach(function (opt) {
                    const txt = opt.textContent.toLowerCase();
                    opt.style.display = txt.includes(q) ? '' : 'none';
                });
            });
        });

        // AJAX submit with client-side JSON validation and server-side error display
        document.getElementById('sales-order-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const errorsDiv = document.getElementById('form-errors');
            errorsDiv.innerHTML = '';
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            const originalText = submitBtn.innerText;
            submitBtn.innerText = 'Creating...';

            try {
                const token = document.querySelector('input[name="_token"]').value;
                const customerId = document.getElementById('customer_id').value;
                if (!customerId) throw new Error('Customer is required.');

                const items = [];
                const itemEls = document.querySelectorAll('#items-container .item');
                for (let i = 0; i < itemEls.length; i++) {
                    const el = itemEls[i];
                    const skuSelect = el.querySelector('select[name*="sku_code"]');
                    const skuCode = skuSelect ? skuSelect.value.trim() : '';
                    if (!skuCode) throw new Error('SKU is required for item ' + (i + 1));
                    const qtyEl = el.querySelector('input[type="number"]');
                    const quantity = qtyEl ? Math.max(1, parseInt(qtyEl.value || '1')) : 1;
                    const custText = el.querySelector('textarea')?.value?.trim() || '';
                    let customization = [];
                    if (custText !== '') {
                        try {
                            customization = JSON.parse(custText);
                        } catch (err) {
                            throw new Error('Invalid JSON in customization for item ' + (i + 1) + ': ' + err.message);
                        }
                    }
                    items.push({ sku_code: skuCode, quantity: quantity, customization_snapshot: customization });
                }

                const payload = { customer_id: parseInt(customerId), items: items };

                const disc = document.querySelector('input[name="discount_amount_minor"]').value;
                const ship = document.querySelector('input[name="shipping_amount_minor"]').value;
                const tax = document.querySelector('input[name="tax_amount_minor"]').value;
                if (disc) payload.discount_amount_minor = parseInt(disc);
                if (ship) payload.shipping_amount_minor = parseInt(ship);
                if (tax) payload.tax_amount_minor = parseInt(tax);

                const advAmt = document.querySelector('input[name="advance_payment[amount_minor]"]').value;
                const advDue = document.querySelector('input[name="advance_payment[due_date]"]').value;
                if (advAmt || advDue) {
                    payload.advance_payment = {};
                    if (advAmt) payload.advance_payment.amount_minor = parseInt(advAmt);
                    if (advDue) payload.advance_payment.due_date = advDue;
                }

                const res = await fetch('{{ route('admin.sales_orders.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json().catch(() => ({}));
                if (res.status === 201) {
                    // redirect to admin order detail
                    const publicId = data.public_id;
                    if (publicId) {
                        window.location.href = '/admin/orders/' + encodeURIComponent(publicId) + '/detail';
                        return;
                    }
                    errorsDiv.innerText = 'Order created but no redirect information returned.';
                } else if (res.status === 422 && data.errors) {
                    let html = '<ul>';
                    for (const field in data.errors) {
                        data.errors[field].forEach(msg => { html += '<li>' + field + ': ' + msg + '</li>'; });
                    }
                    html += '</ul>';
                    errorsDiv.innerHTML = html;
                } else {
                    errorsDiv.innerText = data.message || 'Unexpected error creating order.';
                }
            } catch (err) {
                document.getElementById('form-errors').innerText = err.message || String(err);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    </script>
</body>
</html>
