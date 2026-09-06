@props(['action', 'method' => 'post', 'address' => null, 'idPrefix' => 'address'])
@php($value = fn (string $key, mixed $default = '') => old($key, data_get($address, $key, $default)))

<form class="sf-address-form" method="post" action="{{ $action }}">
    @csrf
    @if(strtolower($method) !== 'post') @method($method) @endif
    <div class="sf-form-grid">
        <label><span>Address type</span><select id="{{ $idPrefix }}-type" name="address_type" required><option value="shipping" @selected($value('address_type', 'both') === 'shipping')>Shipping</option><option value="billing" @selected($value('address_type', 'both') === 'billing')>Billing</option><option value="both" @selected($value('address_type', 'both') === 'both')>Shipping and billing</option></select></label>
        <label><span>Label</span><input id="{{ $idPrefix }}-label" name="label" value="{{ $value('label') }}" placeholder="Home or office" required></label>
        <label><span>Contact name</span><input id="{{ $idPrefix }}-name" name="contact_name" value="{{ $value('contact_name') }}" autocomplete="name" required></label>
        <label><span>Phone</span><input id="{{ $idPrefix }}-phone" name="phone" type="tel" value="{{ $value('phone') }}" autocomplete="tel" required></label>
        <label><span>Company <small>(optional)</small></span><input id="{{ $idPrefix }}-company" name="company_name" value="{{ $value('company_name') }}" autocomplete="organization"></label>
        <label><span>GSTIN <small>(optional)</small></span><input id="{{ $idPrefix }}-gstin" name="gstin" value="{{ $value('gstin') }}" spellcheck="false"></label>
        <label class="sf-field-wide"><span>Address line 1</span><input id="{{ $idPrefix }}-line1" name="address_line_1" value="{{ $value('address_line_1') }}" autocomplete="address-line1" required></label>
        <label class="sf-field-wide"><span>Address line 2 <small>(optional)</small></span><input id="{{ $idPrefix }}-line2" name="address_line_2" value="{{ $value('address_line_2') }}" autocomplete="address-line2"></label>
        <label><span>City</span><input id="{{ $idPrefix }}-city" name="city" value="{{ $value('city') }}" autocomplete="address-level2" required></label>
        <label><span>State</span><input id="{{ $idPrefix }}-state" name="state" value="{{ $value('state') }}" autocomplete="address-level1" required></label>
        <label><span>PIN code</span><input id="{{ $idPrefix }}-postal" name="postal_code" value="{{ $value('postal_code') }}" autocomplete="postal-code" inputmode="numeric" required></label>
        <label><span>Country code</span><input id="{{ $idPrefix }}-country" name="country_code" value="{{ $value('country_code', 'IN') }}" maxlength="2" autocomplete="country" required></label>
        <label class="sf-field-wide"><span>Delivery notes <small>(optional)</small></span><textarea id="{{ $idPrefix }}-notes" name="delivery_notes" maxlength="500">{{ $value('delivery_notes') }}</textarea></label>
    </div>
    <div class="sf-check-row"><label><input type="checkbox" name="is_default_shipping" value="1" @checked((bool) $value('is_default_shipping', false))> Default shipping address</label><label><input type="checkbox" name="is_default_billing" value="1" @checked((bool) $value('is_default_billing', false))> Default billing address</label></div>
    <button class="sf-button" type="submit">Save address</button>
</form>
