<?php

namespace App\Console\Commands;

use GitHub;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\ReleaseNote;

class FetchTagData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:tag-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the github tag values';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $latest = GitHub::repo()->releases()->latest('AdamKyle', 'flare');
        dump($latest);
        if (is_null(ReleaseNote::where('url', $latest['html_url'])->first())) {
            ReleaseNote::create([
                'name'         => $latest['name'],
                'version'      => $latest['tag_name'],
                'url'          => $latest['html_url'],
                'release_date' => Carbon::parse($latest['published_at']),
                'body'         => $latest['body'],
            ]);
        }
    }
}
