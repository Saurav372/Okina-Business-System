<?php

namespace Tests\Unit;

use App\Presenters\ChartGeometryPresenter;
use App\Support\Dashboard\ChartPathBuilder;
use App\Support\Dashboard\ChartPointDTO;
use App\Support\Dashboard\ChartSeriesDTO;
use Tests\TestCase;

class ChartGeometryTest extends TestCase
{
    public function test_presenter_scales_points_to_viewbox_bounds(): void
    {
        $points = collect([
            new ChartPointDTO('Jan', 10.0),
            new ChartPointDTO('Feb', 50.0),
            new ChartPointDTO('Mar', 100.0),
        ]);

        $series = new ChartSeriesDTO('Test Series', $points, 'chart-1', '$');
        $layout = ChartGeometryPresenter::present($series, 500, 200, 40, 20);

        // Verify bounds
        $this->assertEquals(0, $layout->minY); // Zero baseline forced
        $this->assertEquals(100, $layout->maxY); // Rounded up nicely to 100

        $coords = $layout->coordinates;
        $this->assertCount(3, $coords);

        // Verify padding and mapping limits
        $this->assertEquals(40.0, $coords[0]['x']); // x padding left
        $this->assertEquals(500 - 40, $coords[2]['x']); // x padding right
        $this->assertEquals(164.0, $coords[0]['y']); // value 10.0 maps to Y = 164.0
        $this->assertEquals(180.0, $layout->baselineY); // zero baseline maps to Y = 180.0
        $this->assertEquals(20.0, $coords[2]['y']); // value 100 maps to top Y (top Y + padding)
    }

    public function test_presenter_handles_identical_data_points_gracefully(): void
    {
        $points = collect([
            new ChartPointDTO('Jan', 50.0),
            new ChartPointDTO('Feb', 50.0),
        ]);

        $series = new ChartSeriesDTO('Static Series', $points, 'chart-2', '$');

        // This should run without any division-by-zero errors
        $layout = ChartGeometryPresenter::present($series, 500, 200, 40, 20);

        $this->assertGreaterThan($layout->minY, $layout->maxY);
        $this->assertEquals(500 - 40, $layout->coordinates[1]['x']);
    }

    public function test_presenter_shifts_baseline_with_negative_values(): void
    {
        $points = collect([
            new ChartPointDTO('Jan', -50.0),
            new ChartPointDTO('Feb', 100.0),
        ]);

        $series = new ChartSeriesDTO('Mixed Series', $points, 'chart-3', '$');
        $layout = ChartGeometryPresenter::present($series, 500, 200, 40, 20);

        // Verify baseline is in the middle of max and min
        $this->assertLessThan(0.0, $layout->minY);
        $this->assertGreaterThan(0.0, $layout->maxY);

        // Baseline Y coordinate must not be at the bottom (200 - 20 = 180)
        $this->assertLessThan(180.0, $layout->baselineY);
        $this->assertGreaterThan(20.0, $layout->baselineY);
    }

    public function test_path_builder_generates_correct_svg_paths(): void
    {
        $coords = collect([
            ['x' => 40.0, 'y' => 180.0],
            ['x' => 250.0, 'y' => 100.0],
            ['x' => 460.0, 'y' => 20.0],
        ]);

        $linePath = ChartPathBuilder::toLinePath($coords);
        $this->assertEquals('M 40,180 L 250,100 L 460,20', $linePath);

        $areaPath = ChartPathBuilder::toAreaPath($coords, 180.0);
        $this->assertEquals('M 40,180 L 250,100 L 460,20 L 460,180 L 40,180 Z', $areaPath);
    }
}
