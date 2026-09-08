<?php

namespace App\Admin\Monsters\Imports;

use App\Admin\Monsters\Imports\Sheets\MonstersSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonstersImport implements Import, WithMultipleSheets
{
    private MonstersSheet $monstersSheet;

    public function __construct(?MonstersSheet $monstersSheet = null)
    {
        $this->monstersSheet = $monstersSheet ?? new MonstersSheet;
    }

    /**
     * Return the sheets included in the Monsters workbook.
     */
    public function sheets(): array
    {
        return [
            0 => $this->monstersSheet,
        ];
    }

    /**
     * Determine whether the import completed and wrote every workbook row.
     */
    public function wasSuccessful(): bool
    {
        return $this->monstersSheet->wasSuccessful();
    }

    /**
     * Resolve the human-facing validation error for a failed import, when one occurred.
     */
    public function validationError(): ?string
    {
        return $this->monstersSheet->validationError();
    }
}
