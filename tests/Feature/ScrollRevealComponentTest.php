<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScrollRevealComponentTest extends TestCase
{
    /**
     * Test default component rendering and parameters.
     */
    public function test_default_scroll_reveal_parameters(): void
    {
        $rendered = $this->blade('<x-scroll-reveal>Reveal Content</x-scroll-reveal>');

        $html = (string) $rendered;

        // Check tags and classes
        $this->assertStringStartsWith('<div', $html);
        $this->assertStringEndsWith('</div>', trim($html));
        $rendered->assertSee('class="ui-reveal ui-reveal-fade"', false);

        // Check default JS options output
        $rendered->assertSee('threshold: 0.1', false);
        $rendered->assertSee("rootMargin: '0px 0px 0px 0px'", false);
        $rendered->assertSee('if (true) {', false); // onceJs evaluates to true
        $rendered->assertSee('return () => observer.disconnect()', false); // cleanup callback present

        // Check CSS variable default fallback state (delay="none" should not output --reveal-delay)
        $rendered->assertSee('style="--reveal-duration: 300ms"', false);
        $rendered->assertDontSee('--reveal-delay', false);
    }

    /**
     * Test container element tag allowlist and invalid tag fallback.
     */
    public function test_container_tag_allowlist(): void
    {
        // Valid tag from allowlist (section)
        $section = $this->blade('<x-scroll-reveal as="section" />');
        $this->assertStringStartsWith('<section', (string) $section);
        $this->assertStringEndsWith('</section>', trim((string) $section));

        // Valid tag from allowlist (li)
        $li = $this->blade('<x-scroll-reveal as="li" />');
        $this->assertStringStartsWith('<li', (string) $li);
        $this->assertStringEndsWith('</li>', trim((string) $li));

        // Invalid tag falls back to div
        $invalid = $this->blade('<x-scroll-reveal as="banana" />');
        $this->assertStringStartsWith('<div', (string) $invalid);
        $this->assertStringEndsWith('</div>', trim((string) $invalid));
    }

    /**
     * Test speed and delay mapping to CSS custom properties.
     */
    public function test_speed_and_delay_custom_properties(): void
    {
        // Speed and delay token mapping
        $tokens = $this->blade('<x-scroll-reveal speed="fast" delay="sm" />');
        $tokens->assertSee('style="--reveal-duration: 150ms; --reveal-delay: 150ms"', false);

        // Slow speed and lg delay mapping
        $slow = $this->blade('<x-scroll-reveal speed="slow" delay="lg" />');
        $slow->assertSee('style="--reveal-duration: 500ms; --reveal-delay: 500ms"', false);

        // Custom duration and delay preserved
        $custom = $this->blade('<x-scroll-reveal speed="800ms" delay="0.5s" />');
        $custom->assertSee('style="--reveal-duration: 800ms; --reveal-delay: 0.5s"', false);

        // Invalid speed falls back to normal (300ms)
        $invalid = $this->blade('<x-scroll-reveal speed="unknown" delay="none" />');
        $invalid->assertSee('style="--reveal-duration: 300ms"', false);
    }

    /**
     * Test threshold clamping limits.
     */
    public function test_threshold_clamping_limits(): void
    {
        // Inside bounds
        $inBounds = $this->blade('<x-scroll-reveal threshold="0.55" />');
        $inBounds->assertSee('threshold: 0.55', false);

        // Below 0 clamps to 0
        $belowZero = $this->blade('<x-scroll-reveal threshold="-0.5" />');
        $belowZero->assertSee('threshold: 0', false);

        // Above 1 clamps to 1
        $aboveOne = $this->blade('<x-scroll-reveal threshold="1.8" />');
        $aboveOne->assertSee('threshold: 1', false);
    }

    /**
     * Test root margin configuration passing.
     */
    public function test_root_margin_configuration(): void
    {
        $rendered = $this->blade('<x-scroll-reveal root-margin="0px 0px -100px 0px" />');
        $rendered->assertSee("rootMargin: '0px 0px -100px 0px'", false);
    }

    /**
     * Test once lifecycle trigger options.
     */
    public function test_once_lifecycle_behavior(): void
    {
        // once=true (default) triggers unobserve
        $onceTrue = $this->blade('<x-scroll-reveal :once="true" />');
        $onceTrue->assertSee('if (true) {', false);
        $onceTrue->assertSee('observer.unobserve(entry.target);', false);

        // once=false toggles revealed state back and forth
        $onceFalse = $this->blade('<x-scroll-reveal :once="false" />');
        $onceFalse->assertSee('if (false) {', false);
        $onceFalse->assertSee('else if (!false) {', false);
        $onceFalse->assertSee('revealed = false;', false);
    }

    /**
     * Test progressive enhancement class bindings.
     */
    public function test_progressive_enhancement_class_bindings(): void
    {
        $rendered = $this->blade('<x-scroll-reveal type="slide-up" />');

        // Static classes are always emitted (keeps element visible if JS is disabled)
        $rendered->assertSee('class="ui-reveal ui-reveal-slide-up"', false);

        // Alpine binds state changes dynamically
        $rendered->assertSee(':class="revealed ? \'is-revealed\' : \'is-hidden\'"', false);
    }
}
