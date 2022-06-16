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

    public function formatForEditor(array $sections): array {
        $sections = array_values($sections);

        foreach ($sections as $index => $section) {
            $section['content'] = str_replace('</p><p>', '</p><p><br></p><p>', $section['content']);

            $sections[$index] = $section;
        }

        return $sections;
    }

    protected function storeContents(string $pageName, array $sections) {

        $page = InfoPage::where('page_name', Str::kebab(strtolower($pageName)))->first();

        if (!is_null($page)) {
            $pageSections   = array_values($page->page_sections);

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

    public function deleteStoredImages(array $sections, string $pageName) {
        foreach ($sections as $section) {
            if (!is_null($section['content_image_path'])) {
                Storage::disk('info-sections-images')->delete('/' . $pageName . $section['content_image_path']);
            }
        }
    }

    public function updatePage(InfoPage $page, array $params) {
        if (isset($params['delete'])) {
            $this->deleteSection($page, $params['display_order']);

            return;
        }

        return $this->updateOrCreateSection($page, $params);
    }

    protected function updateOrCreateSection(InfoPage $page, array $params) {
        $sections     = array_values($page->page_sections);
        $sectionIndex = null;

        foreach ($sections as $index => $section) {
            if ($section['display_order'] === $params['display_order']) {
                $sectionIndex = $index;
            }
        }

        if (!is_null($sectionIndex)) {
            $section = $sections[$sectionIndex];

            $section = $this->uploadNewImage($section, $params, $page->page_name);

            $content = str_replace('<p><br></p>', '', $params['content']);
            $content = str_replace('<p>&nbsp;</p>', '', $content);

            $section['content']             = $content;
            $section['live_wire_component'] = $params['live_wire_component'];

            $sections[$sectionIndex] = $section;

            $page->update([
                'page_sections' => $sections
            ]);

            return;
        }

        $section = $this->createSection($params);

        $sections[] = $section;

        $page->update([
            'page_sections' => $sections
        ]);
    }

    protected function uploadNewImage(array $section, array $params, string $pageName) {
        if ($params['content_image'] !== 'null') {
            Storage::disk('info-sections-images')->delete('/' . $pageName . $section['content_image_path']);
            $path = Storage::disk('info-sections-images')->putFile($pageName, $params['content_image']);

            $section['content_image_path'] = $path;
        }

        return $section;
    }


    protected function deleteSection(InfoPage $page, int $displayOrder) {
        $sections     = $page->page_sections;
        $sectionIndex = null;

        foreach ($sections as $index => $section) {
            if ($section['display_order'] === $displayOrder) {
                $sectionIndex = $index;
            }
        }

        if (!is_null($sectionIndex)) {
            $section = $sections[$sectionIndex];

            Storage::disk('info-sections-images')->delete('/' . $page->name . $section['content_image_path']);

            unset($sections[$sectionIndex]);
        }

        $page->update([
            'page_sections' => $sections,
        ]);
    }

    protected function createSection(array $params): array {
        $pageName = $params['page_name'];
        $path     = null;

        if ($params['content_image'] !== 'null') {
            $path = Storage::disk('info-sections-images')->putFile(Str::kebab(strtolower($pageName)), $params['content_image']);
        }

        $content = str_replace('<p><br></p>', '', $params['content']);
        $content = str_replace('<p>&nbsp;</p>', '', $content);

        $sections = [
            'content'             => $content,
            'content_image_path'  => $path,
            'live_wire_component' => $params['live_wire_component'] !== 'null' ? $params['live_wire_component'] : null,
            'display_order'       => isset($params['order']) ? $params['order'] : $params['display_order'],
        ];

        return $sections;
    }
}
