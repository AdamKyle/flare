<?php

namespace App\Admin\Import\LocationTemplates\Sheets;

use App\Flare\Models\LocationTemplate;
use App\Game\Maps\Values\LocationTemplateType;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class LocationTemplatesSheet implements ToCollection
{
    /**
     * Import Location Template rows from the uploaded spreadsheet, updating an existing Location Template by name or creating a new one.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $data = array_combine($rows[0]->toArray(), $row->toArray());
            $data = $this->cleanData($data);

            if (empty($data)) {
                continue;
            }

            $this->assertDescriptionIsUnique($data);

            LocationTemplate::updateOrCreate([
                'name' => $data['name'],
            ], $data);
        }
    }

    /**
     * Guard against a workbook row reusing a description already claimed by a differently named Location Template.
     */
    private function assertDescriptionIsUnique(array $data): void
    {
        $descriptionTaken = LocationTemplate::where('description', $data['description'])
            ->where('name', '!=', $data['name'])
            ->exists();

        if ($descriptionTaken) {
            throw ValidationException::withMessages([
                'description' => 'Location template descriptions must be unique.',
            ]);
        }
    }

    /**
     * Normalize and validate a raw Location Template workbook row into persistable attributes.
     */
    private function cleanData(array $row): array
    {
        $type = LocationTemplateType::tryFrom((string) ($row['type'] ?? ''));

        if (is_null($type) || empty($row['name']) || empty($row['description'])) {
            return [];
        }

        return [
            'name' => $row['name'],
            'description' => $row['description'],
            'type' => $type->value,
            'is_port' => $type === LocationTemplateType::PORT,
            'can_players_enter' => ! array_key_exists('can_players_enter', $row) || is_null($row['can_players_enter'])
                ? true
                : (bool) $row['can_players_enter'],
        ];
    }
}
