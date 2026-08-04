<?php

namespace App\Game\Skills\Transformers\Traits;

use App\Game\Skills\Transformers\SkillsTransformer;
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
