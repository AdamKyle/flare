<?php

namespace App\Admin\Kingdoms\Services;

use App\Admin\Kingdoms\Exceptions\KingdomWorkbookException;
use App\Admin\Kingdoms\Exports\KingdomsExport;
use App\Admin\Kingdoms\Imports\KingdomsImport;
use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Models\GameBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KingdomExcelService
{
    use ResponseBuilder;

    /**
     * @param UpdateKingdomsService $updateKingdomsService
     * @param BuildingUnitAssignmentService $buildingUnitAssignmentService
     */
    public function __construct(
        private readonly UpdateKingdomsService $updateKingdomsService,
        private readonly BuildingUnitAssignmentService $buildingUnitAssignmentService,
    ) {}

    /**
     * Download the Kingdom workbook.
     *
     * @return BinaryFileResponse
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new KingdomsExport, 'kingdoms.xlsx', ExcelWriter::XLSX);
    }

    /**
     * Import the Kingdom workbook atomically, then assign and refresh every Building definition for player Kingdoms.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function import(UploadedFile $file): array
    {
        try {
            DB::transaction(function () use ($file): void {
                Excel::import(new KingdomsImport($this->buildingUnitAssignmentService), $file);
            });
        } catch (KingdomWorkbookException $exception) {
            return $this->errorResult($exception->getMessage());
        }

        GameBuilding::orderBy('id')->each(function (GameBuilding $gameBuilding): void {
            $this->updateKingdomsService->assignNewBuildingsToCharacters($gameBuilding);
            $this->updateKingdomsService->updateKingdomBuildings($gameBuilding);
        });

        return $this->successResult([
            'message' => 'Kingdom data imported successfully.',
        ]);
    }
}
