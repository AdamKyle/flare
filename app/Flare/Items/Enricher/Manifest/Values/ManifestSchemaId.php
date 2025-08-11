<?php

namespace App\Flare\Items\Enricher\Manifest\Values;

use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Flare\Items\Enricher\Manifest\EquippableManifest;

enum ManifestSchemaId: string
{
    case EQUIPPABLE = 'equippable';

    public function schema(): ManifestSchema
    {
        return match ($this) {
            self::EQUIPPABLE => new EquippableManifest,
        };
    }
}
