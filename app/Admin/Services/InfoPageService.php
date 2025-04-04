<?php

namespace App\Admin\Services;

use App\Flare\Models\InfoPage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InfoPageService
{
    /**
     * Create a new info page.
     *
     * @param array $params
     * @return InfoPage
     */
    public function createPage(array $params): InfoPage
    {
        return $this->create($params);
    }

    /**
     * Format sections for the editor.
     *
     * @param array $sections
     * @return array
     */
    public function formatForEditor(array $sections): array
    {
        $sections = array_values($sections);

        foreach ($sections as $index => $section) {
            $section['content'] = str_replace('</p><p>', '</p><p><br></p><p>', $section['content']);
            $sections[$index] = $section;
        }

        array_multisort(array_column($sections, 'order'), SORT_ASC, $sections);

        return $sections;
    }

    /**
     * Create a new info page section and store its contents.
     *
     * @param array $params
     * @return InfoPage
     */
    private function create(array $params): InfoPage
    {
        $section = $this->createSection($params);

        return $this->storeContents($params['page_name'], $section);
    }

    /**
     * Store page content in the database.
     *
     * @param string $pageName
     * @param array $sections
     * @return InfoPage
     */
    private function storeContents(string $pageName, array $sections): InfoPage
    {
        $page = InfoPage::where('page_name', Str::kebab(strtolower($pageName)))->first();

        if (! is_null($page)) {
            $pageSections = array_values($page->page_sections);
            $pageSections[] = $sections;
            $page->update(['page_sections' => $pageSections]);
        } else {
            $pageSections = [$sections];
            $page = InfoPage::create([
                'page_name' => Str::kebab(strtolower($pageName)),
                'page_sections' => $pageSections,
            ]);
        }

        return $page->refresh();
    }

    /**
     * Delete stored images for a page.
     *
     * @param array $sections
     * @param string $pageName
     * @return void
     */
    public function deleteStoredImages(array $sections, string $pageName): void
    {
        foreach ($sections as $section) {
            if (! is_null($section['content_image_path'])) {
                Storage::disk('info-sections-images')->delete('/'.$pageName.$section['content_image_path']);
            }
        }
    }

    /**
     * Update an existing info page.
     *
     * @param InfoPage $page
     * @param array $params
     * @return void
     */
    public function updatePage(InfoPage $page, array $params): void
    {
        $pageSections = array_values($page->page_sections);

        $index = array_search($params['order'], array_column($pageSections, 'order'));

        if ($index !== false) {
            $pageSections[$index] = $this->updateExistingSection($page, $pageSections[$index], $params);
        }

        if ($index === false) {
            $pageSections[] = $this->createSection($params);
        }

        $pageSections = $this->reOrderSectionAfterSectionDeletion($pageSections);

        $page->update(['page_sections' => $pageSections]);
    }

    public function addSections(InfoPage $page, array $section): void {
        $pageSections = $this->formatForEditor($page->page_sections);

        $params = ['page_name' => $page->page_name, ...$section];

        $indexToInsertAt = $section['insert_at_index'];

        $createdSection = $this->createSection($params);

        array_splice($pageSections, $indexToInsertAt, 0, [$createdSection]);

        $sections = $this->reOrderSectionAfterSectionDeletion($pageSections);

        $page->update(['page_sections' => $sections]);
    }

    /**
     * Delete a section from an info page.
     *
     * @param InfoPage $page
     * @param int $order
     * @return InfoPage
     */
    public function deleteSectionFromPage(InfoPage $page, int $order): InfoPage
    {
        $sections = $page->page_sections;
        $sectionIndex = array_search($order, array_column($sections, 'order'));

        if ($sectionIndex !== false) {
            $section = $sections[$sectionIndex];
            Storage::disk('info-sections-images')->delete('/'.$page->name.$section['content_image_path']);
            unset($sections[$sectionIndex]);
        }

        $sections = $this->reOrderSectionAfterSectionDeletion($sections);
        $page->update(['page_sections' => $sections]);

        return $page->refresh();
    }

    /**
     * Update an existing section.
     *
     * @param InfoPage $page
     * @param array $section
     * @param array $params
     * @return array
     */
    private function updateExistingSection(InfoPage $page, array $section, array $params): array
    {
        $section = $this->uploadNewImage($section, $params, $page->page_name);

        $content = str_replace(['<p><br></p>', '<p>&nbsp;</p>', '../../'], '', $params['content']);

        $section['content'] = $content;
        $section['live_wire_component'] = $params['live_wire_component'];
        $section['item_table_type'] = $params['item_table_type'];
        $section['order'] = isset($params['new_order']) ? (int) $params['new_order'] : (int) $params['order'];

        return $section;
    }

    /**
     * Upload a new image for a section.
     *
     * @param array $section
     * @param array $params
     * @param string $pageName
     * @return array
     */
    private function uploadNewImage(array $section, array $params, string $pageName): array
    {
        if (isset($params['content_image'])) {
            Storage::disk('info-sections-images')->delete('/'.$pageName.$section['content_image_path']);
            $path = Storage::disk('info-sections-images')->putFile($pageName, $params['content_image']);
            $section['content_image_path'] = $path;
        }

        return $section;
    }

    /**
     * Reorder all sections after one section is deleted.
     *
     * @param array $sections
     * @return array
     */
    private function reOrderSectionAfterSectionDeletion(array $sections): array
    {
        $orderStart = 0;

        foreach ($sections as $index => $section) {
            $section['order'] = $orderStart;

            $sections[$index] = $section;

            $orderStart++;
        }

        return $sections;
    }

    /**
     * Create a new section.
     *
     * @param array $params
     * @return array
     */
    private function createSection(array $params): array
    {
        $pageName = $params['page_name'];
        $path = null;

        if (isset($params['content_image'])) {
            $path = Storage::disk('info-sections-images')->putFile(Str::kebab(strtolower($pageName)), $params['content_image']);
        }

        $content = '';

        if (!is_null($params['content'])) {
            $content = str_replace('<p><br></p>', '', $params['content']);
            $content = str_replace('<p>&nbsp;</p>', '', $content);
        }

        return [
            'order' => (int) $params['order'],
            'content' => $content,
            'content_image_path' => $path,
            'live_wire_component' => $params['live_wire_component'] !== 'null' ? $params['live_wire_component'] : null,
            'item_table_type' => $params['item_table_type'] !== 'null' ? $params['item_table_type'] : null,
        ];
    }
}
