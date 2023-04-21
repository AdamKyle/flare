<?php

namespace App\Flare\Transformers\Traits;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Illuminate\Support\Collection as SupportCollection;
use App\Flare\Transformers\SkillsTransformer;

trait SkillsTransformerTrait {

    /**
     * Transform the character skills.
     *
     * @param SupportCollection $skills
     * @return array
     */
    protected function fetchSkills(SupportCollection $skills): array {
        $manager = resolve(Manager::class);

        $skills = new Collection($skills, new SkillsTransformer);

        return $manager->createData($skills)->toArray();
    }
}
