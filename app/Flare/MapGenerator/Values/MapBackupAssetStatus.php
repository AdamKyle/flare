<?php

namespace App\Flare\MapGenerator\Values;

enum MapBackupAssetStatus: string
{
    case ALREADY_VALID = 'already_valid';
    case RESTORED = 'restored';
    case REPAIRED = 'repaired';
    case MISSING_BACKUP = 'missing_backup';
    case FAILED = 'failed';
}
