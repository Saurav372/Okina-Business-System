<?php

namespace Tests\Feature;

use Tests\TestCase;

class PageTransitionTest extends TestCase
{
    /**
     * Test layouts contain the unique transition naming targets.
     */
    public function test_layout_elements_contain_unique_view_transition_names(): void
    {
        $rendered = $this->blade('<x-layouts.admin>Dashboard Page Content</x-layouts.admin>');

        $html = (string) $rendered;

        // Verify Alpine Navigator wrapper is attached
        $rendered->assertSee('x-data="pageNavigator"', false);

        // Verify layout transition name classes are present
        $rendered->assertSee('layout-sidebar', false);
        $rendered->assertSee('layout-header', false);
        $rendered->assertSee('layout-main', false);

        // Verify uniqueness (exist exactly once in layout page)
        $this->assertEquals(1, substr_count($html, 'layout-sidebar'), 'Duplicate layout-sidebar class found.');
        $this->assertEquals(1, substr_count($html, 'layout-header'), 'Duplicate layout-header class found.');
        $this->assertEquals(1, substr_count($html, 'layout-main'), 'Duplicate layout-main class found.');
    }
}
