<?php

namespace Tests\Feature;

use Tests\TestCase;

class SkeletonComponentTest extends TestCase
{
    /**
     * Test default skeleton parameters.
     */
    public function test_default_skeleton_parameters(): void
    {
        $rendered = $this->blade('<x-skeleton />');

        $rendered->assertSee('role="presentation"', false);
        $rendered->assertSee('aria-hidden="true"', false);
        $rendered->assertSee('data-skeleton', false);
        $rendered->assertSee('data-variant="line"', false);
        $rendered->assertSee('data-animation="shimmer"', false);
        $rendered->assertSee('class="skeleton-base select-none pointer-events-none overflow-hidden block w-full h-4 rounded-[var(--radius-sm)] skeleton-shimmer"', false);
    }

    /**
     * Test variant size and shape mapping.
     */
    public function test_variant_sizing_and_shape_mapping(): void
    {
        // Block variant mapping
        $block = $this->blade('<x-skeleton variant="block" />');
        $block->assertSee('class="skeleton-base select-none pointer-events-none overflow-hidden block w-full h-32 rounded-[var(--skeleton-radius)] skeleton-shimmer"', false);

        // Circle variant mapping
        $circle = $this->blade('<x-skeleton variant="circle" />');
        $circle->assertSee('class="skeleton-base select-none pointer-events-none overflow-hidden block w-10 h-10 shrink-0 aspect-square rounded-full skeleton-shimmer"', false);
    }

    /**
     * Test custom dimensions: width, height, and unit preservation.
     */
    public function test_custom_dimensions_and_units(): void
    {
        // Numeric inputs cast to px
        $numeric = $this->blade('<x-skeleton width="200" height="80" />');
        $numeric->assertSee('style="width: 200px;height: 80px;"', false);

        // String inputs preserve CSS units
        $units = $this->blade('<x-skeleton width="12rem" height="50%" />');
        $units->assertSee('style="width: 12rem;height: 50%;"', false);

        // Width-only preserves default variant height class
        $widthOnly = $this->blade('<x-skeleton width="200" variant="line" />');
        $widthOnly->assertSee('style="width: 200px;"', false);
        $widthOnly->assertSee('h-4', false); // default line height class is preserved

        // Height-only preserves default variant width class
        $heightOnly = $this->blade('<x-skeleton height="80" variant="line" />');
        $heightOnly->assertSee('style="height: 80px;"', false);
        $heightOnly->assertSee('w-full', false); // default line width class is preserved
    }

    /**
     * Test fallbacks for invalid inputs.
     */
    public function test_invalid_variant_and_animation_fallbacks(): void
    {
        // Invalid variant falls back to line size/radius defaults
        $invalidVariant = $this->blade('<x-skeleton variant="banana" />');
        $invalidVariant->assertSee('data-variant="banana"', false);
        $invalidVariant->assertSee('w-full h-4', false);
        $invalidVariant->assertSee('rounded-[var(--radius-sm)]', false);

        // Invalid animation falls back to shimmer classes
        $invalidAnimate = $this->blade('<x-skeleton animate="unknown" />');
        $invalidAnimate->assertSee('data-animation="unknown"', false);
        $invalidAnimate->assertSee('skeleton-shimmer', false);
    }

    /**
     * Test class attribute merges and duplicate class prevention.
     */
    public function test_class_merging_and_duplication_prevention(): void
    {
        $merged = $this->blade('<x-skeleton class="mt-4 shadow-sm" rounded="md" />');

        $html = (string) $merged;
        $merged->assertSee('mt-4 shadow-sm', false);
        $merged->assertSee('rounded-[var(--radius-md)]', false);

        // Parse class list to verify duplicate class names do not exist
        preg_match('/class="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches, 'Class attribute was not found.');

        $classes = explode(' ', $matches[1]);
        $duplicates = array_diff_assoc($classes, array_unique($classes));
        $this->assertEmpty($duplicates, 'Duplicate classes found: '.implode(', ', $duplicates));
    }

    /**
     * Test slot loading state transitions.
     */
    public function test_slots_loading_states(): void
    {
        $template = <<<'HTML'
<x-skeleton :loading="$loading" variant="line" class="h-10">
    <span class="actual-content">Loaded content details</span>
</x-skeleton>
HTML;

        // When loading is true
        $loadingTrue = $this->blade($template, ['loading' => true]);
        $loadingTrue->assertSee('aria-busy="true"', false);
        $loadingTrue->assertSee('data-skeleton', false);
        $loadingTrue->assertDontSee('Loaded content details', false);

        // When loading is false
        $loadingFalse = $this->blade($template, ['loading' => false]);
        $loadingFalse->assertSee('aria-busy="false"', false);
        $loadingFalse->assertDontSee('data-skeleton', false);
        $loadingFalse->assertSee('Loaded content details', false);
    }

    /**
     * Test empty slot compilation safety.
     */
    public function test_empty_slot_compilation(): void
    {
        // Wrapper modes with empty slots should render without failure
        $emptySlot = $this->blade('<x-skeleton :loading="true"></x-skeleton>');
        $emptySlot->assertSee('data-skeleton', false);

        $whitespaceSlot = $this->blade('<x-skeleton :loading="true">   </x-skeleton>');
        $whitespaceSlot->assertSee('data-skeleton', false);
    }
}
