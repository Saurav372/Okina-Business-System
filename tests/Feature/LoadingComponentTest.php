<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoadingComponentTest extends TestCase
{
    /**
     * Test x-spinner default attributes.
     */
    public function test_renders_default_spinner_correctly(): void
    {
        $rendered = $this->blade('<x-spinner />');

        $rendered->assertSee('class="animate-spin-custom shrink-0 h-4 w-4 text-current"', false);
        $rendered->assertSee('aria-hidden="true"', false);
        $rendered->assertSee('stroke-width="4"', false);
        $rendered->assertDontSee('role="status"', false);
    }

    /**
     * Test x-spinner size and intent fallback.
     */
    public function test_spinner_fallbacks(): void
    {
        // Invalid size falls back to sm
        $invalidSize = $this->blade('<x-spinner size="huge" />');
        $invalidSize->assertSee('h-4 w-4', false);

        // Invalid intent falls back to current
        $invalidIntent = $this->blade('<x-spinner intent="gold" />');
        $invalidIntent->assertSee('text-current', false);
    }

    /**
     * Test x-spinner stroke thickness validation and clamping.
     */
    public function test_spinner_thickness_clamping(): void
    {
        // Clamp below minimum
        $clampMin = $this->blade('<x-spinner thickness="0" />');
        $clampMin->assertSee('stroke-width="1"', false);

        $clampNegative = $this->blade('<x-spinner thickness="-5" />');
        $clampNegative->assertSee('stroke-width="1"', false);

        // Clamp above maximum
        $clampMax = $this->blade('<x-spinner thickness="12" />');
        $clampMax->assertSee('stroke-width="8"', false);

        // Valid parsing of numeric strings
        $validString = $this->blade('<x-spinner thickness="5" />');
        $validString->assertSee('stroke-width="5"', false);

        // String cast fallback
        $stringErr = $this->blade('<x-spinner thickness="abc" />');
        $stringErr->assertSee('stroke-width="1"', false); // (int)"abc" => 0 -> clamped to 1
    }

    /**
     * Test x-spinner accessibility options.
     */
    public function test_spinner_accessibility(): void
    {
        // Screen reader label present
        $withLabel = $this->blade('<x-spinner srOnlyLabel="Loading users" />');
        $withLabel->assertSee('role="status"', false);
        $withLabel->assertSee('aria-live="polite"', false);
        $withLabel->assertSee('<span class="sr-only">Loading users</span>', false);
        $withLabel->assertDontSee('aria-hidden="true"', false);

        // Screen reader label absent (default)
        $noLabel = $this->blade('<x-spinner />');
        $noLabel->assertSee('aria-hidden="true"', false);
        $noLabel->assertDontSee('role="status"', false);
    }

    /**
     * Test inline loader layout and custom parameters.
     */
    public function test_inline_loader(): void
    {
        // Default text
        $default = $this->blade('<x-loading.inline />');
        $default->assertSee('Loading...', false);
        $default->assertSee('class="animate-spin-custom shrink-0 h-4 w-4 text-[color:var(--color-primary-600)]"', false);

        // Custom text via attribute
        $customText = $this->blade('<x-loading.inline text="Saving changes..." />');
        $customText->assertSee('Saving changes...', false);

        // Custom text via slot
        $slotText = $this->blade('<x-loading.inline>Processing...</x-loading.inline>');
        $slotText->assertSee('Processing...', false);

        // Custom size & intent
        $customSpinner = $this->blade('<x-loading.inline size="lg" intent="success" />');
        $customSpinner->assertSee('h-8 w-8 text-[color:var(--color-success-500)]', false);
    }

    /**
     * Test overlay standalone mode (without slot).
     */
    public function test_overlay_standalone_mode(): void
    {
        $standalone = $this->blade('<x-loading.overlay show="loadingState" label="Syncing data..." />');

        // Check motion wrapping and busy state
        $standalone->assertSee('x-show="loadingState"', false);
        $standalone->assertSee(':aria-busy="loadingState"', false);
        $standalone->assertSee('x-cloak', false);

        // Check defaults: absolute z-30 positioning, surface color, xs blur
        $standalone->assertSee('class="ui-motion absolute inset-0 z-30 flex flex-col items-center justify-center pointer-events-auto bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xs"', false);
        $standalone->assertSee('Syncing data...', false);

        // Standalone should NOT emit wrapper divs
        $html = (string) $standalone;
        $this->assertEquals(0, substr_count($html, 'relative overflow-hidden'));
        $this->assertEquals(0, substr_count($html, ':inert'));

        // Fullscreen positioning and custom tone/blur
        $fullscreen = $this->blade('<x-loading.overlay show="loadingState" fullscreen tone="glass" blur="md" />');
        $fullscreen->assertSee('class="ui-motion fixed inset-0 z-[var(--z-overlay,100)] flex flex-col items-center justify-center pointer-events-auto bg-white/40 dark:bg-black/40 backdrop-blur-md"', false);
    }

    /**
     * Test overlay wrapper mode (with slot).
     */
    public function test_overlay_wrapper_mode(): void
    {
        $wrapper = $this->blade('<x-loading.overlay show="loadingState" label="Syncing..."><p>Card Content</p></x-loading.overlay>');

        // Check wrapper structural layers
        $wrapper->assertSee('class="relative overflow-hidden w-full h-full"', false);
        $wrapper->assertSee(':inert="loadingState"', false);
        $wrapper->assertSee('Card Content', false);

        // Ensure standalone elements are nested inside
        $wrapper->assertSee(':aria-busy="loadingState"', false);
        $wrapper->assertSee('Syncing...', false);
    }
}
