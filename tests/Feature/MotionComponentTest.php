<?php

namespace Tests\Feature;

use Tests\TestCase;

class MotionComponentTest extends TestCase
{
    /**
     * Test default component rendering and presets.
     */
    public function test_renders_default_attributes_correctly(): void
    {
        $rendered = $this->blade('<x-motion>Content</x-motion>');

        $rendered->assertSee('Content');
        $rendered->assertSee('x-cloak');
        $rendered->assertSee('class="ui-motion"', false);
        $rendered->assertSee('x-transition:enter="transition ease-[var(--motion-ease)] duration-[var(--motion-normal)]"', false);
        $rendered->assertSee('x-transition:enter-start="opacity-0"', false);
        $rendered->assertSee('x-transition:enter-end="opacity-100"', false);
        $rendered->assertSee('x-transition:leave="transition ease-[var(--ease-in)] duration-[var(--duration-200)]"', false);
        $rendered->assertSee('x-transition:leave-start="opacity-100"', false);
        $rendered->assertSee('x-transition:leave-end="opacity-0"', false);
    }

    /**
     * Test semantic and numeric durations.
     */
    public function test_normalizes_durations(): void
    {
        // Semantic fast mapping
        $fast = $this->blade('<x-motion duration="fast">Content</x-motion>');
        $fast->assertSee('duration-[var(--motion-fast)]', false);

        // Semantic slow mapping
        $slow = $this->blade('<x-motion duration="slow">Content</x-motion>');
        $slow->assertSee('duration-[var(--motion-slow)]', false);

        // Numeric duration mapping (whitelisted value)
        $numericOk = $this->blade('<x-motion duration="150">Content</x-motion>');
        $numericOk->assertSee('duration-150', false);

        // Numeric duration fallback (invalid value fallback to normal)
        $numericErr = $this->blade('<x-motion duration="420">Content</x-motion>');
        $numericErr->assertSee('duration-[var(--motion-normal)]', false);
    }

    /**
     * Test whitelisted and invalid delays.
     */
    public function test_validates_delays(): void
    {
        // Valid delay
        $validDelay = $this->blade('<x-motion delay="75">Content</x-motion>');
        $validDelay->assertSee('delay-75', false);

        // Invalid delay should be ignored/omitted
        $invalidDelay = $this->blade('<x-motion delay="123">Content</x-motion>');
        $invalidDelay->assertDontSee('delay-123', false);
    }

    /**
     * Test whitelisted and invalid easings.
     */
    public function test_validates_easings(): void
    {
        // Valid easing
        $bounce = $this->blade('<x-motion easing="bounce">Content</x-motion>');
        $bounce->assertSee('ease-[var(--ease-bounce)]', false);

        // Invalid easing falls back to ease
        $invalid = $this->blade('<x-motion easing="crazy">Content</x-motion>');
        $invalid->assertSee('ease-[var(--motion-ease)]', false);
    }

    /**
     * Test whitelisted and invalid origins.
     */
    public function test_validates_origins(): void
    {
        // Valid origin on transform scale
        $originTop = $this->blade('<x-motion type="scale" origin="top-left">Content</x-motion>');
        $originTop->assertSee('origin-top-left', false);

        // Invalid origin falls back to center on transform scale
        $invalidOrigin = $this->blade('<x-motion type="scale" origin="skyward">Content</x-motion>');
        $invalidOrigin->assertSee('origin-center', false);
    }

    /**
     * Test parsing of multiple comma/space separated effects.
     */
    public function test_parses_multiple_effects(): void
    {
        $multi = $this->blade('<x-motion type="fade" effect="scale slide-up">Content</x-motion>');
        // enter-start should combine opacity-0 (fade), scale-95 (scale), translate-y-4 (slide-up)
        $multi->assertSee('x-transition:enter-start="opacity-0 scale-95 translate-y-4"', false);
        $multi->assertSee('x-transition:enter-end="opacity-100 scale-100 translate-y-0"', false);
    }

    /**
     * Test spacing, comma, and empty effect parsing normalization.
     */
    public function test_normalizes_whitespace_and_punctuation(): void
    {
        // Punctuation and whitespace combinations
        $normalized = $this->blade('<x-motion type="fade" effect=" scale   , slide-up , , fade ">Content</x-motion>');
        $normalized->assertSee('x-transition:enter-start="opacity-0 scale-95 translate-y-4"', false);

        // Empty effect behaves like null
        $empty = $this->blade('<x-motion effect="">Content</x-motion>');
        $empty->assertSee('x-transition:enter-start="opacity-0"', false);
        $empty->assertDontSee('scale', false);
    }

    /**
     * Test deduplication of classes.
     */
    public function test_deduplicates_classes(): void
    {
        // Specifying fade twice shouldn't double classes in the enter-start sequence
        $duplicate = $this->blade('<x-motion type="fade" effect="fade">Content</x-motion>');

        $duplicate->assertSee('x-transition:enter-start="opacity-0"', false);
        $duplicate->assertDontSee('opacity-0 opacity-0');
    }

    /**
     * Test transform disabling prop.
     */
    public function test_disables_transforms_when_false(): void
    {
        // Setting :transform="false" should strip scale and translate but preserve fade opacity
        $noTransform = $this->blade('<x-motion type="fade" effect="scale slide-up" :transform="false">Content</x-motion>');
        $noTransform->assertSee('x-transition:enter-start="opacity-0"', false);
        $noTransform->assertDontSee('scale-95', false);
        $noTransform->assertDontSee('translate-y-4', false);
    }

    /**
     * Test collapse markup structure and transform-stripping behavior.
     */
    public function test_collapse_markup_and_auto_transforms(): void
    {
        // Grid collapse wrapper is rendered and transform effects are stripped
        $collapse = $this->blade('<x-motion type="collapse" effect="scale slide-up" show="open">Content</x-motion>');

        $collapse->assertSee('class="ui-motion grid transition-all duration-[var(--motion-normal)] ease-[var(--motion-ease)] overflow-hidden"', false);
        $collapse->assertSee(':class="open ? \'grid-rows-[1fr] opacity-100\' : \'grid-rows-[0fr] opacity-0\'"', false);
        $collapse->assertSee('<div class="min-h-0 overflow-hidden">', false);

        // Confirm x-transition enter/leave is absent (replaced by grid transition)
        $collapse->assertDontSee('x-transition:enter', false);
    }

    /**
     * Test appear prop.
     */
    public function test_handles_appear_prop(): void
    {
        $appear = $this->blade('<x-motion :appear="true">Content</x-motion>');
        $appear->assertSee('x-transition:enter.appear="transition ease-[var(--motion-ease)] duration-[var(--motion-normal)]"', false);
    }

    /**
     * Test xCloak prop logic.
     */
    public function test_handles_x_cloak_prop(): void
    {
        // Cloak active by default
        $default = $this->blade('<x-motion>Content</x-motion>');
        $default->assertSee('x-cloak', false);

        // Cloak disabled
        $noCloak = $this->blade('<x-motion :xCloak="false">Content</x-motion>');
        $noCloak->assertDontSee('x-cloak', false);
    }
}
