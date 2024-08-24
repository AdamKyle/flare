<?php

namespace App\Flare\Transformers\Traits;

use App\Flare\Transformers\SkillsTransformer;
use Illuminate\Support\Collection as SupportCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

trait SkillsTransformerTrait
{
    /**
     * Transform the character skills.
     */
    protected function fetchSkills(SupportCollection $skills): array
    {
        $manager = resolve(Manager::class);

        $skills = new Collection($skills, new SkillsTransformer);

        return $manager->createData($skills)->toArray();
    }
}
