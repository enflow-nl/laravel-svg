<?php

namespace Enflow\Svg\Test;

use Enflow\Svg\Exceptions\SvgMustBeRendered;
use Enflow\Svg\Exceptions\SvgNotFoundException;
use Enflow\Svg\Spritesheet;
use Enflow\Svg\Svg;
use Illuminate\Support\Str;
use Spatie\Snapshots\MatchesSnapshots;

class SvgTest extends TestCase
{
    use MatchesSnapshots;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('svg.packs', [
            'custom' => __DIR__ . '/fixtures/custom',
            'icons' => [
                'path' => __DIR__ . '/fixtures/icons',
                'auto_size_on_viewbox' => true,
            ],
        ]);
    }

    public function test_svg_rendering()
    {
        $svg = svg('clock'); // Get from "custom", the first of the array

        $this->assertMatchesXmlSnapshot($svg->render());
        $this->assertEquals($svg->pack->name, 'custom');

        $spritesheet = app(Spritesheet::class);
        $this->assertCount(1, $spritesheet->svgs);

        $this->assertMatchesHtmlSnapshot($spritesheet->toHtml());
    }

    public function test_auto_size_for_svg_rendering()
    {
        /** @var Svg $svg */
        $svg = svg('house')->class('mr-4'); // Get from "icons"

        $rendered = $svg->render();

        $this->assertMatchesXmlSnapshot($rendered);
        $this->assertEquals($svg->pack->name, 'icons');
        $this->assertStringContainsString('1.125em', $rendered);
        $this->assertStringContainsString('svg-auto-size', $rendered);
        $this->assertStringContainsString('mr-4', $rendered);
        $this->assertStringContainsString('focusable="false"', $rendered);
    }

    public function test_viewbox_parsing()
    {
        /** @var Svg $svg */
        $svg = svg('house');

        $svg->render();

        $this->assertEquals('0 0 576 512', implode(' ', $svg->viewBox()));
    }

    public function test_all_render_methods_contain_the_same()
    {
        $svg = svg('clock');

        $this->assertEquals($svg->render(), $svg->toHtml());
        $this->assertEquals($svg->toHtml(), (string)$svg);
        $this->assertEquals($svg->render(), (string)$svg);
    }

    public function test_that_svg_is_only_once_in_spritesheet()
    {
        svg('clock')->render();
        svg('clock')->render();
        svg('house')->render();

        $spritesheet = app(Spritesheet::class);
        $this->assertCount(2, $spritesheet->svgs);
        $this->assertEquals(2, substr_count($spritesheet->toHtml(), '<symbol'));
    }

    public function test_exception_when_rendering_non_existing_svg()
    {
        $this->expectException(SvgNotFoundException::class);

        $this->assertInstanceOf(Svg::class, svg('non-existing'));

        svg('non-existing')->toHtml();
    }

    public function test_exception_when_using_viewbox_method_without_rendering()
    {
        $this->expectException(SvgMustBeRendered::class);

        svg('house')->viewBox();
    }
}