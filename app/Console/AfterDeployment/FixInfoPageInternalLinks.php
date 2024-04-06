<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\InfoPage;
use Illuminate\Console\Command;

class FixInfoPageInternalLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:info-page-internal-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        $infoPages = InfoPage::all();

        foreach ($infoPages as $infoPage) {
            $sections = $infoPage->page_sections;

            foreach ($sections as $index => $section) {
                $content = $sections[$index]['content'];

                $content = str_replace('/../', '/', $content);

                $sections[$index]['content'] = str_replace('../../', '/', $content);
            }

            $infoPage->update([
                'page_sections' => $sections,
            ]);
        }
    }
}
