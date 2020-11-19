<?php

namespace Tests\Unit\Flare\View\Livewire\Core;

use App\Flare\View\Livewire\Core\FormWizard;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormWizardTest extends TestCase
{
    use RefreshDatabase;

    public function testComponentLoads() {
        Livewire::test(FormWizard::class, [
            'views' => [
                'admin.affixes.partials.affix-details',
                'admin.affixes.partials.affix-modifier',
            ],
            'model'     => [
                'id' => 1
            ],
            'modelName' => 'itemAffix',
            'steps' => [
                'Affix Details',
                'Affix Modifiers',
            ],
            'finishRoute' => 'affixes.list',
        ])->call('sessionMessage', 'success', 'sample')
          ->call('storeModel', ['id' => 1], true, 'admin.affixes.partials.affix-modifier')
          ->call('nextStep', 1, false)
          ->call('nextStep', 1, true)
          ->call('previousStep', 0)
          ->call('finish', 1, false, [])
          ->call('finish', 1, true, [
              'type' => 'success',
              'message' => 'sample'
          ]);
    }

    public function testComponentLoadsAndFinishesWithNoMessage() {
        Livewire::test(FormWizard::class, [
            'views' => [
                'admin.affixes.partials.affix-details',
                'admin.affixes.partials.affix-modifier',
            ],
            'model'     => [
                'id' => 1
            ],
            'modelName' => 'itemAffix',
            'steps' => [
                'Affix Details',
                'Affix Modifiers',
            ],
            'finishRoute' => 'affixes.list',
        ])->call('nextStep', 1, false)
          ->call('nextStep', 1, true)
          ->call('previousStep', 0)
          ->call('finish', 1, false, [])
          ->call('finish', 1, true);
    }
}
