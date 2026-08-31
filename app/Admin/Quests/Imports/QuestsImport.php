<?php

namespace App\Admin\Quests\Imports;

use App\Admin\Quests\Imports\Sheets\QuestsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestsImport implements Import, WithMultipleSheets
{
    private QuestsSheet $questsSheet;

    /**
     * @param  QuestsSheet|null  $questsSheet  Quests workbook sheet; a real instance is used when none is supplied.
     */
    public function __construct(?QuestsSheet $questsSheet = null)
    {
        $this->questsSheet = $questsSheet ?? new QuestsSheet;
    }

    /**
     * Return the sheets included in the Quests workbook.
     *
     * @return array<int, QuestsSheet> Quests workbook sheets.
     */
    public function sheets(): array
    {
        return [
            0 => $this->questsSheet,
        ];
    }

    /**
     * Determine whether the import completed and wrote every workbook row.
     *
     * @return bool Whether the import succeeded.
     */
    public function wasSuccessful(): bool
    {
        return $this->questsSheet->wasSuccessful();
    }

    /**
     * Resolve the human-facing validation error for a failed import, when one occurred.
     *
     * @return string|null Validation error message, or null when the import succeeded.
     */
    public function validationError(): ?string
    {
        return $this->questsSheet->validationError();
    }
}
