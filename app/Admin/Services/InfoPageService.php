<?php

namespace App\Admin\Services;

use App\Flare\Models\InfoPage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfoPageService {

    public function createPage(array $params) {
        $this->create($params);
    }

    protected function create(array $params) {
        $section = $this->createSection($params);

        $this->storeContents($params['page_name'], $section);
    }

    protected function storeContents(string $pageName, array $sections) {

        $page = InfoPage::where('page_name', Str::kebab(strtolower($pageName)))->first();

        if (!is_null($page)) {
            $pageSections   = $page->page_sections;

            $pageSections[] = $sections;

            $page->update(['page_sections' => $pageSections]);
        } else {
            $pageSections   = [];
            $pageSections[] = $sections;

            InfoPage::create([
                'page_name' => Str::kebab(strtolower($pageName)),
                'page_sections' => $pageSections,
            ]);
        }
    }

    protected function deleteStoredImages(array $sections, string $pageName) {
        foreach ($sections as $section) {
            if (!is_null($section['content_image_path'])) {
                Storage::disk('info-sections-images')->delete('/' . $pageName . $sections['content_image_path']);
            }
        }
    }

    protected function createSection(array $params): array {
        $pageName = $params['page_name'];
        $path     = null;

        if ($params['content_image'] !== 'null') {
            $path = Storage::disk('info-sections-images')->putFile(Str::kebab(strtolower($pageName)), $params['content_image']);
        }

        $sections = [
            'content'             => $params['content'],
            'content_image_path'  => $path,
            'live_wire_component' => $params['live_wire_component'] !== 'null' ? $params['live_wire_component'] : null,
            'display_order'       => $params['order'],
        ];

        return $sections;
    }
}
