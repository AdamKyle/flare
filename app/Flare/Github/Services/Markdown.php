<?php

namespace App\Flare\Github\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown {

    /**
     * Convert markdown to html.
     *
     * @param string $text
     * @return string
     */
    public function convertToHtml(string $text): string {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($text)->getContent();
    }
}
