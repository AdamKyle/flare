<?php

namespace App\Flare\Github\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown
{
    /**
     * Convert markdown to html.
     */
    public function convertToHtml(string $text): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($text)->getContent();
    }
}
