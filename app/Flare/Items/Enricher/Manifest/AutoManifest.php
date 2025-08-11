<?php

declare(strict_types=1);

namespace App\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\Values\ManifestSchemaId;

/**
 * Marks an enrich() method to auto-build a manifest using the provided schema class.
 *
 * Usage:
 *   #[AutoManifest(ManifestSchemaId::EQUIPPABLE)]
 *   public function enrich(Item $item): Item { ... }
 *
 * Note:
 * - We fully-qualify PHP's built-in \Attribute to avoid conflicts with
 *   Illuminate\Database\Eloquent\Casts\Attribute.
 * - The $schema parameter is a class-string; instantiate it via reflection
 *   when processing the attribute.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class AutoManifest
{
    /**
     * @param ManifestSchemaId $schema Fully-qualified schema class name
     */
    public function __construct(public ManifestSchemaId $schema)
    {
    }
}
