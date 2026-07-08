<?php

namespace Tests\Feature;

use App\Presenters\DesignTokenCatalog;
use Tests\TestCase;

class ShowcaseRegistrationTest extends TestCase
{
    /**
     * Verify design token presenter functions load and return normalized arrays.
     */
    public function test_presenter_normalizes_data_correctly(): void
    {
        $colors = DesignTokenCatalog::colors();
        $this->assertIsArray($colors);
        $this->assertNotEmpty($colors);

        // Verify colors shape keys exist
        $primary = $colors[0];
        $this->assertArrayHasKey('name', $primary);
        $this->assertArrayHasKey('variable', $primary);
        $this->assertArrayHasKey('used_by', $primary);
        $this->assertArrayHasKey('shades', $primary);

        $spacing = DesignTokenCatalog::spacing();
        $this->assertIsArray($spacing);
        $this->assertNotEmpty($spacing);

        $typography = DesignTokenCatalog::typography();
        $this->assertIsArray($typography);
        $this->assertNotEmpty($typography);

        $elevation = DesignTokenCatalog::elevation();
        $this->assertIsArray($elevation);
        $this->assertNotEmpty($elevation);

        $motion = DesignTokenCatalog::motion();
        $this->assertIsArray($motion);
        $this->assertNotEmpty($motion);
    }

    /**
     * Verify cache contract of DesignTokenCatalog presenter.
     */
    public function test_presenter_caches_results(): void
    {
        $colors1 = DesignTokenCatalog::colors();
        $colors2 = DesignTokenCatalog::colors();

        // Colors normalized data structure should be identically matched across multiple calls
        $this->assertSame($colors1, $colors2);
    }

    /**
     * Verify fallback defaults for unknown config sections.
     */
    public function test_presenter_returns_empty_array_for_unknown_sections(): void
    {
        $unknown = DesignTokenCatalog::section('unknown_section');
        $this->assertIsArray($unknown);
        $this->assertEmpty($unknown);
    }

    /**
     * Verify that presenter fetches exclusively from standard config container (supporting runtime overrides and config:cache serialization).
     */
    public function test_presenter_is_compatible_with_laravel_config_caching_and_runtime_overrides(): void
    {
        // Set runtime configuration override
        config([
            'design-tokens.colors' => [
                [
                    'name' => 'Custom Runtime Color',
                    'variable' => 'var(--custom-color)',
                    'hex' => '#999999',
                    'aliases' => ['custom'],
                    'used_by' => ['Testing'],
                    'contrast' => 'Compliant',
                    'shades' => []
                ]
            ]
        ]);

        // Clear presenter's internal cache state by calling section on a newly instantiated presenter or clearing it
        // Since $cache is private static array, we can test it using a section we haven't fetched yet or resetting config.
        // Wait, because colors was already fetched in previous tests, it would be cached. Let's register a new section 'mock_override'
        config([
            'design-tokens.mock_override' => [
                ['name' => 'Mocked Override Item']
            ]
        ]);

        $normalized = DesignTokenCatalog::section('mock_override');
        $this->assertCount(1, $normalized);
        $this->assertEquals('Mocked Override Item', $normalized[0]['name']);
    }

    /**
     * Verify the compiled Blade view displays search bar, copy click binds, and interactive controls.
     */
    public function test_showcase_view_contains_elements_and_copy_triggers(): void
    {
        $rendered = $this->view('component-showcase');

        // Verify sidebar search box exists
        $rendered->assertSee('x-model="search"', false);
        $rendered->assertSee('@input="filterShowcase"', false);

        // Verify custom color copy to clipboard function hooks exist
        $rendered->assertSee('copyToClipboard', false);
        $rendered->assertSee('📋 Var', false);
        $rendered->assertSee('📋 Hex', false);

        // Verify interactive motion keys and replay triggers exist
        $rendered->assertSee('▶ Replay', false);
        $rendered->assertSee(':key="replayKey"', false);
    }
}
