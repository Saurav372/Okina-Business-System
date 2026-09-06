<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProgressComponentTest extends TestCase
{
    /**
     * Test default progress rendering and indeterminate defaults.
     */
    public function test_renders_default_progress_correctly(): void
    {
        $rendered = $this->blade('<x-progress />');

        $rendered->assertSee('role="progressbar"', false);
        $rendered->assertSee('aria-busy="true"', false);
        $rendered->assertSee('aria-valuemin="0"', false);
        $rendered->assertSee('aria-valuemax="100"', false);
        $rendered->assertDontSee('aria-valuenow', false);
        $rendered->assertDontSee('style="width:', false);
        $rendered->assertDontSee('aria-valuetext', false);
    }

    /**
     * Test determinate mode attributes and style bindings.
     */
    public function test_determinate_mode_rendering(): void
    {
        $determinate = $this->blade('<x-progress value="60" />');

        $determinate->assertSee('role="progressbar"', false);
        $determinate->assertSee('aria-valuenow="60"', false);
        $determinate->assertSee('style="width: 60%;"', false);
        $determinate->assertSee('aria-valuetext="60%"', false);
        $determinate->assertDontSee('aria-busy="true"', false);
    }

    /**
     * Test parameters clamping and range validations.
     */
    public function test_bounds_clamping_and_range_normalization(): void
    {
        // Clamp below min
        $clampMin = $this->blade('<x-progress value="-10" min="0" max="100" />');
        $clampMin->assertSee('aria-valuenow="0"', false);
        $clampMin->assertSee('style="width: 0%;"', false);

        // Clamp above max
        $clampMax = $this->blade('<x-progress value="150" min="0" max="100" />');
        $clampMax->assertSee('aria-valuenow="100"', false);
        $clampMax->assertSee('style="width: 100%;"', false);

        // Normalize range if max <= min
        $normRange = $this->blade('<x-progress value="10" min="100" max="0" />');
        $normRange->assertSee('aria-valuemin="100"', false);
        $normRange->assertSee('aria-valuemax="101"', false); // Normalized max = min + 1
    }

    /**
     * Test styling limits: min-width and rounded class mappings.
     */
    public function test_progress_style_rules(): void
    {
        // Extremely small progress gets min-width: 4px in determinate mode
        $smallProgress = $this->blade('<x-progress value="1.5" min="0" max="100" />');
        $smallProgress->assertSee('style="width: 1.5%; min-width: 4px;"', false);

        // Indeterminate small progress should NEVER get min-width
        $indetProgress = $this->blade('<x-progress />');
        $indetProgress->assertDontSee('min-width', false);

        // Rounded corners mappings
        $roundFull = $this->blade('<x-progress rounded="full" />');
        $roundFull->assertSee('rounded-full', false);

        $roundMd = $this->blade('<x-progress rounded="md" />');
        $roundMd->assertSee('rounded-md', false);

        $roundNone = $this->blade('<x-progress rounded="none" />');
        $roundNone->assertSee('rounded-none', false);
    }

    /**
     * Test floating-point precision formatting.
     */
    public function test_floating_point_percent_formatting(): void
    {
        // Round decimal cleanly to 1 decimal place
        $floatVal = $this->blade('<x-progress value="59.9" min="0" max="100" showLabel />');
        $floatVal->assertSee('59.9%', false);

        // Round decimal to 0 decimal places if close to integer
        $intVal = $this->blade('<x-progress value="60" min="0" max="100" showLabel />');
        $intVal->assertSee('60%', false);
        $intVal->assertDontSee('60.0%', false);
    }

    /**
     * Test label custom options and precedence.
     */
    public function test_label_options_and_precedence(): void
    {
        // Custom label overrides percentage
        $customLabel = $this->blade('<x-progress value="60" label="Uploading file..." showLabel />');
        $customLabel->assertSee('Uploading file...', false);
        $customLabel->assertDontSee('<span>60%</span>', false);
        $customLabel->assertSee('aria-valuetext="Uploading file..."', false);

        // Visually hidden labels still render screen reader text
        $hiddenLabel = $this->blade('<x-progress value="60" label="Background Sync" :showLabel="false" />');
        $hiddenLabel->assertSee('aria-valuetext="Background Sync"', false);
        $hiddenLabel->assertDontSee('Background Sync</span>', false);

        // showLabel=false still renders aria-valuetext when custom label is supplied
        $noShowLabel = $this->blade('<x-progress value="60" label="Background Processing" :showLabel="false" />');
        $noShowLabel->assertSee('aria-valuetext="Background Processing"', false);
        $noShowLabel->assertDontSee('Background Processing</span>', false);
    }

    /**
     * Test accessibility fields: srOnlyLabel, aria-label mapping.
     */
    public function test_accessible_labels(): void
    {
        $srLabel = $this->blade('<x-progress value="60" srOnlyLabel="File uploads" />');
        $srLabel->assertSee('aria-label="File uploads"', false);
    }

    /**
     * Test inline label visual threshold constraints.
     */
    public function test_inline_label_threshold_hiding(): void
    {
        // Inline label shown at >= 10%
        $shown = $this->blade('<x-progress value="15" showLabel labelPosition="inline" />');
        $shown->assertSee('15%', false);

        // Inline label hidden at < 10%
        $hidden = $this->blade('<x-progress value="5" showLabel labelPosition="inline" />');
        $hidden->assertDontSee('>5%</span>', false); // Check the visible text is absent in span
    }

    /**
     * Test striped and animated state logic combinations.
     */
    public function test_striped_and_animation_conflicts(): void
    {
        // Animated stripes on determinate bar
        $animDeterminate = $this->blade('<x-progress value="60" striped animated />');
        $animDeterminate->assertSee('bg-progress-striped', false);
        $animDeterminate->assertSee('animate-progress-stripes', false);

        // Animated stripes should NOT render in indeterminate mode (value="")
        $animIndeterminate = $this->blade('<x-progress value="" striped animated />');
        $animIndeterminate->assertSee('bg-progress-striped', false);
        $animIndeterminate->assertDontSee('animate-progress-stripes', false);
        $animIndeterminate->assertDontSee('style="width:', false); // Assert style width is absent
    }
}
