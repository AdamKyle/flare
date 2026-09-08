<?php

namespace App\Admin\Quests\Imports;

use App\Admin\Quests\Imports\Sheets\QuestsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestsImport implements Import, WithMultipleSheets
{
    private QuestsSheet $questsSheet;

    public function __construct(?QuestsSheet $questsSheet = null)
    {
        $this->questsSheet = $questsSheet ?? new QuestsSheet;
    }

    /**
     * Return the sheets included in the Quests workbook.
     */
    public function sheets(): array
    {
        return [
            0 => $this->questsSheet,
        ];
    }

    /**
     * Determine whether the import completed and wrote every workbook row.
     */
    public function wasSuccessful(): bool
    {
        return $this->questsSheet->wasSuccessful();
    }

    /**
     * Resolve the human-facing validation error for a failed import, when one occurred.
     */
    public function validationError(): ?string
    {
        return $this->questsSheet->validationError();
    }
}
