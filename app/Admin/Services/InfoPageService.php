<?php

namespace App\Admin\Services;

use App\Flare\Models\InfoPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfoPageService {

    public function storePage(array $params) {
        $this->store($params);
    }

    protected function store(array $params) {
        $pageId = $params['page_id'];

        $page = InfoPage::find($pageId);

        if (!is_null($page)) {
            $this->addSectionsToCache($params, $params['page_name'], $page);
        } else {
            $this->addSectionsToCache($params, $params['page_name'], $page);
        }

        if (filter_var($params['final_section'], FILTER_VALIDATE_BOOLEAN)) {
            $this->storeContents($params['page_name'], $page);
        }
    }

    protected function storeContents(string $pageName, InfoPage $page = null) {
        if (!is_null($page)) {
            $this->deleteStoredImages($page->page_sections, $page->page_name);

            $page->update(['page_sections' => Cache::get($pageName)]);
        } else {
            InfoPage::create([
                'page_name'     => Str::kebab(strtolower($pageName)),
                'page_sections' => Cache::get($pageName),
            ]);

            Cache::delete($pageName);
        }
    }

    protected function deleteStoredImages(array $sections, string $pageName) {
        foreach ($sections as $section) {
            if (!is_null($section['content_image_path'])) {
                Storage::disk('info-sections-images')->delete('/' . $pageName . $sections['content_image_path']);
            }
        }
    }

    protected function addSectionsToCache(array $params, string $pageName, InfoPage $page = null) {
        if (!Cache::has($pageName)) {
            $sections = Cache::get($pageName);

            $sections[] = $this->createSection($params);

            Cache::put($pageName, $sections);
        } else {
            Cache::put($pageName, $this->createSection($params, $page));
        }
    }

    protected function createSection(array $params, InfoPage $page = null): array {
        $pageName = $params['page_name'];
        $path     = null;

        if ($params['content_image'] !== 'null') {
            $path = Storage::disk('info-sections-images')->putFile(Str::kebab(strtolower($pageName)), $params['content_image']);
        }

        $sections = [
            'content'             => $params['content'],
            'content_image_path'  => $path,
            'live_wire_component' => $params['live_wire_component'] !== 'null' ? $params['live_wire_component'] : null,
        ];

        return $sections;
    }
}
