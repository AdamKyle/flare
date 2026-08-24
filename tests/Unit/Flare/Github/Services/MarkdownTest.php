<?php

namespace Tests\Unit\Flare\Github\Services;

use App\Flare\Github\Services\Markdown;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    public function test_clean_markdown_returns_empty_string_for_null(): void
    {
        $this->assertSame('', (new Markdown)->cleanMarkdown(null));
    }

    public function test_clean_markdown_trims_the_string(): void
    {
        $this->assertSame('Hello', (new Markdown)->cleanMarkdown('  Hello  '));
    }

    public function test_clean_markdown_removes_backslashes(): void
    {
        $this->assertSame('Hello World', (new Markdown)->cleanMarkdown('Hello\\ World'));
    }

    public function test_clean_markdown_normalizes_newlines(): void
    {
        $this->assertSame("Line1\nLine2\nLine3", (new Markdown)->cleanMarkdown("Line1\r\nLine2\rLine3"));
    }

    public function test_clean_markdown_decodes_html_entities(): void
    {
        $this->assertSame('Tom & Jerry', (new Markdown)->cleanMarkdown('Tom &amp; Jerry'));
    }

    public function test_convert_to_html_converts_markdown_syntax(): void
    {
        $html = (new Markdown)->convertToHtml('# Heading');

        $this->assertStringContainsString('<h1>Heading</h1>', $html);
    }

    public function test_convert_to_html_strips_unsafe_html(): void
    {
        $html = (new Markdown)->convertToHtml('<script>alert(1)</script>Hello');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_convert_to_html_blocks_unsafe_links(): void
    {
        $html = (new Markdown)->convertToHtml('[click me](javascript:alert(1))');

        $this->assertStringNotContainsString('href="javascript:alert(1)"', $html);
    }
}
