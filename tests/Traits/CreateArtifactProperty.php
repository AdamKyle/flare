<?php

namespace Tests\Traits;

use App\Flare\Models\ArtifactProperty;

trait CreateArtifactProperty {

    public function createArtifactProperty(array $options = []): ArtifactProperty {
        return factory(ArtifactProperty::class)->create($options);
    }
}
