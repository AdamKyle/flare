<?php

namespace App\Flare\Github\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown
{
    /**
     * Clean up the string that contains markdown.
     */
    public function cleanMarkdown(?string $markdown): string
    {

        if (is_null($markdown)) {
            return '';
        }

        $markdown = trim($markdown);

        $markdown = str_replace('\\', '', $markdown);

        $markdown = preg_replace("/\r\n|\r|\n/", "\n", $markdown);

        return html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
    }

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
