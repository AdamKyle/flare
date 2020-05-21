<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class CreateItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:item {name} {type} {base_damage} {cost}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an item';

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
        $item = Item::create([
            'name'        => $this->argument('name'),
            'base_damage' => $this->argument('base_damage'),
            'cost'        => $this->argument('cost'),
            'type'        => $this->argument('type'),
        ]);

        $this->info('Created item: ' . $item->id);
    }
}
