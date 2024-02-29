<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\InfoPage;
use Illuminate\Console\Command;

class FillInfoSectionsWithItemTableType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fill:info-sections-with-item-table-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill in the info section entries with the new item_table_type field';

    /**
     * Execute the console command.
     */
    public function handle() {

        $infoSection = InfoPage::all();

        foreach ($infoSection as $section) {
            $pageSections = $section->page_sections;

            foreach ($pageSections as $index => $pageDetails) {
                $pageDetails['item_table_type'] = null;

                $pageSections[$index] = $pageDetails;
            }

            $section->update([
                'page_sections' => $pageSections,
            ]);
        }
    }
}
