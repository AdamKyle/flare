<?php

namespace App\Game\Core\Items\Enricher\Manifest\Values;

use App\Game\Core\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Game\Core\Items\Enricher\Manifest\EquippableManifest;

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
