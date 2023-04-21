<?php

namespace App\Admin\Services;

use App\Flare\Models\InfoPage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfoPageService {

    public function createPage(array $params): InfoPage {
        return $this->create($params);
    }

    protected function create(array $params): InfoPage {
        $section = $this->createSection($params);

        return $this->storeContents($params['page_name'], $section);
    }

    public function formatForEditor(array $sections): array {
        $sections = array_values($sections);

        foreach ($sections as $index => $section) {
            $section['content'] = str_replace('</p><p>', '</p><p><br></p><p>', $section['content']);

            $sections[$index] = $section;
        }

        array_multisort(array_column($sections, 'order'), SORT_ASC, $sections);

        return $sections;
    }

    protected function storeContents(string $pageName, array $sections): InfoPage {

        $page = InfoPage::where('page_name', Str::kebab(strtolower($pageName)))->first();

        if (!is_null($page)) {
            $pageSections   = array_values($page->page_sections);

            $pageSections[] = $sections;

            $page->update(['page_sections' => $pageSections]);
        } else {
            $pageSections   = [];
            $pageSections[] = $sections;

            $page = InfoPage::create([
                'page_name' => Str::kebab(strtolower($pageName)),
                'page_sections' => $pageSections,
            ]);
        }

        return $page->refresh();
    }

    public function deleteStoredImages(array $sections, string $pageName) {
        foreach ($sections as $section) {
            if (!is_null($section['content_image_path'])) {
                Storage::disk('info-sections-images')->delete('/' . $pageName . $section['content_image_path']);
            }
        }
    }

    public function updatePage(InfoPage $page, array $params) {
        return $this->updateOrCreateSection($page, $params);
    }

    public function deleteSectionFromPage(InfoPage $page, int $order): InfoPage {
        $sections     = $page->page_sections;
        $sectionIndex = null;

        foreach ($sections as $index => $section) {
            if ((int) $section['order'] === (int) $order) {
                $sectionIndex = $index;
            }
        }

        if (!is_null($sectionIndex)) {
            $section = $sections[$sectionIndex];

            Storage::disk('info-sections-images')->delete('/' . $page->name . $section['content_image_path']);

            unset($sections[$sectionIndex]);
        }


        $sections = $this->reOrderSectionAfterSectionDeletion($sections);

        $page->update([
            'page_sections' => $sections,
        ]);

        return $page->refresh();
    }

    protected function updateOrCreateSection(InfoPage $page, array $params) {
        $sections        = array_values($page->page_sections);
        $sectionToUpdate = null;
        $sectionIndex    = null;

        foreach ($sections as $index => $section) {
            if ((int) $section['order'] === (int) $params['order']) {
                $sectionToUpdate = $section;
                $sectionIndex    = $index;

                break;
            }
        }

        if (!is_null($sectionToUpdate)) {
            $sectionToUpdate = $this->uploadNewImage($section, $params, $page->page_name);

            $content = str_replace('<p><br></p>', '', $params['content']);
            $content = str_replace('<p>&nbsp;</p>', '', $content);

            $sectionToUpdate['content']             = $content;
            $sectionToUpdate['live_wire_component'] = $params['live_wire_component'];
            $sectionToUpdate['order']               = (int) $params['order'];

            $sections[$sectionIndex] = $sectionToUpdate;

            $page->update([
                'page_sections' => $sections
            ]);
        }

        if (is_null($sectionToUpdate)) {
            $section = $this->createSection($params);

            $sections[] = $section;

            $page->update([
                'page_sections' => $sections
            ]);
        }
    }

    protected function uploadNewImage(array $section, array $params, string $pageName) {
        if (isset($params['content_image'])) {
            Storage::disk('info-sections-images')->delete('/' . $pageName . $section['content_image_path']);
            $path = Storage::disk('info-sections-images')->putFile($pageName, $params['content_image']);

            $section['content_image_path'] = $path;
        }

        return $section;
    }

    protected function reOrderSectionAfterSectionDeletion(array $sections): array {
        $currentOrder = null;

        foreach ($sections as $index => $section) {
            if (is_null($currentOrder) && $index === 0) {
                $currentOrder = ((int) $section['order']) + 1;

                continue;
            }

            if (!is_null($currentOrder)) {
                $sections[$index]['order'] = $currentOrder;

                $currentOrder++;
            }
        }

        return $sections;
    }

    protected function createSection(array $params): array {
        $pageName = $params['page_name'];
        $path     = null;

        if (isset($params['content_image'])) {
            $path = Storage::disk('info-sections-images')->putFile(Str::kebab(strtolower($pageName)), $params['content_image']);
        }

        $content = str_replace('<p><br></p>', '', $params['content']);
        $content = str_replace('<p>&nbsp;</p>', '', $content);

        $sections = [
            'order'               => (int) $params['order'],
            'content'             => $content,
            'content_image_path'  => $path,
            'live_wire_component' => $params['live_wire_component'] !== 'null' ? $params['live_wire_component'] : null,
        ];

        return $sections;
    }
}
