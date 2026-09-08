<?php

namespace App\Admin\Quests\Imports\Sheets;

use App\Admin\Quests\Services\QuestService;
use App\Admin\Quests\Support\QuestImportGraphValidator;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Npc;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Core\Values\FeatureType;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestsSheet implements ToCollection
{
    private bool $successful = false;

    private ?string $validationError = null;

    public function __construct(
        private readonly QuestService $questService = new QuestService,
    ) {}

    /**
     * Import Quest rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $rawRows = $this->extractMeaningfulRows($rows);

        if ($this->hasDuplicateNames($rawRows)) {
            $this->fail('The Quests workbook contains duplicate Quest names.');

            return;
        }

        $nameToId = $this->buildNameToId($rawRows);
        $resolvedRowsById = [];

        foreach ($rawRows as $rawRow) {
            $questData = $this->resolveRow($rawRow, $nameToId);

            if (is_null($questData)) {
                $this->fail("Unable to import Quest \"{$rawRow['name']}\": one or more referenced records or values could not be resolved.");

                return;
            }

            $resolvedRowsById[$nameToId[$rawRow['name']]] = $questData;
        }

        $graphError = (new QuestImportGraphValidator($nameToId, $this->graphRows($resolvedRowsById)))->validate();

        if (! is_null($graphError)) {
            $this->fail($graphError);

            return;
        }

        $this->writeRows($resolvedRowsById);

        $this->successful = true;
    }

    /**
     * Determine whether the import completed and wrote every workbook row.
     */
    public function wasSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Resolve the human-facing validation error for a failed import, when one occurred.
     */
    public function validationError(): ?string
    {
        return $this->validationError;
    }

    /**
     * Record why the import failed. Zero rows are written once this is called.
     */
    private function fail(string $message): void
    {
        $this->validationError = $message;
    }

    /**
     * Extract every meaningful row (up to the first blank Quest name) from the uploaded workbook.
     */
    private function extractMeaningfulRows(Collection $rows): array
    {
        $headers = $rows[0]->toArray();
        $rawRows = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $rawRow = array_combine($headers, $row->toArray());

            if (is_null($rawRow['name'] ?? null) || $rawRow['name'] === '') {
                break;
            }

            $rawRows[] = $rawRow;
        }

        return $rawRows;
    }

    /**
     * Determine whether the uploaded workbook names the same Quest more than once.
     */
    private function hasDuplicateNames(array $rawRows): bool
    {
        $names = array_map(fn (array $row) => $row['name'], $rawRows);

        return count($names) !== count(array_unique($names));
    }

    /**
     * Build the workbook Quest-name graph used to resolve Quest references before writes.
     */
    private function buildNameToId(array $rawRows): array
    {
        $nameToId = Quest::pluck('id', 'name')->all();
        $syntheticId = -1;

        foreach ($rawRows as $rawRow) {
            $name = $rawRow['name'];

            if (! array_key_exists($name, $nameToId)) {
                $nameToId[$name] = $syntheticId;
                $syntheticId--;
            }
        }

        return $nameToId;
    }

    /**
     * Build the Quest relationship edges used for graph-cycle validation.
     */
    private function graphRows(array $resolvedRowsById): array
    {
        return array_map(fn (array $questData) => [
            'parent_quest_id' => $questData['parent_quest_id'],
            'required_quest_id' => $questData['required_quest_id'],
            'required_quest_chain' => $questData['required_quest_chain'] ?? [],
        ], $resolvedRowsById);
    }

    /**
     * Persist Quest rows in two passes so workbook-local Quest references resolve safely.
     */
    private function writeRows(array $resolvedRowsById): void
    {
        $syntheticToRealId = [];
        $questsById = [];

        foreach ($resolvedRowsById as $id => $questData) {
            $baseData = $questData;
            unset($baseData['parent_quest_id'], $baseData['required_quest_id'], $baseData['required_quest_chain']);

            $existingQuest = Quest::where('name', $questData['name'])->first();

            if (is_null($existingQuest)) {
                $quest = Quest::create($baseData + ['parent_quest_id' => null, 'required_quest_id' => null, 'required_quest_chain' => null]);
                $syntheticToRealId[$id] = $quest->id;
            } else {
                $existingQuest->update($baseData);
                $quest = $existingQuest;
            }

            $questsById[$id] = $quest;
        }

        foreach ($resolvedRowsById as $id => $questData) {
            $quest = $questsById[$id];
            $previousParentId = $quest->parent_quest_id;

            $resolvedParentId = $this->resolveWrittenId($questData['parent_quest_id'], $syntheticToRealId);
            $resolvedRequiredId = $this->resolveWrittenId($questData['required_quest_id'], $syntheticToRealId);
            $resolvedChainIds = array_map(
                fn (int $chainId) => $this->resolveWrittenId($chainId, $syntheticToRealId),
                $questData['required_quest_chain'] ?? []
            );

            $quest->update([
                'parent_quest_id' => $resolvedParentId,
                'required_quest_id' => $resolvedRequiredId,
                'required_quest_chain' => $resolvedChainIds,
            ]);

            $this->questService->reconcileParentFlags($previousParentId, $resolvedParentId);
        }

        $this->reconcileImportedParentFlags($questsById);
    }

    /**
     * Derive imported Quest parent flags from the persisted parent-child hierarchy.
     */
    private function reconcileImportedParentFlags(array $questsById): void
    {
        foreach ($questsById as $quest) {
            $quest->update([
                'is_parent' => Quest::where('parent_quest_id', $quest->id)->exists(),
            ]);
        }
    }

    /**
     * Resolve a workbook reference id to the persisted Quest id written to the database.
     */
    private function resolveWrittenId(?int $id, array $syntheticToRealId): ?int
    {
        if (is_null($id)) {
            return null;
        }

        return $syntheticToRealId[$id] ?? $id;
    }

    /**
     * Resolve a single raw Quest row into normalized, validated Quest attributes.
     */
    private function resolveRow(array $rawRow, array $nameToId): ?array
    {
        $data = $this->applyDefaults($rawRow);

        $data['npc_id'] = $this->resolveByName(Npc::class, 'real_name', $rawRow['npc_id'] ?? null);
        $data['item_id'] = $this->resolveByName(Item::class, 'name', $rawRow['item_id'] ?? null);
        $data['raid_id'] = $this->resolveByName(Raid::class, 'name', $rawRow['raid_id'] ?? null);
        $data['reward_item'] = $this->resolveByName(Item::class, 'name', $rawRow['reward_item'] ?? null);
        $data['secondary_required_item'] = $this->resolveByName(Item::class, 'name', $rawRow['secondary_required_item'] ?? null);
        $data['access_to_map_id'] = $this->resolveByName(GameMap::class, 'name', $rawRow['access_to_map_id'] ?? null);
        $data['faction_game_map_id'] = $this->resolveByName(GameMap::class, 'name', $rawRow['faction_game_map_id'] ?? null);
        $data['unlocks_passive_id'] = $this->resolveByName(PassiveSkill::class, 'name', $rawRow['unlocks_passive_id'] ?? null);
        $data['assisting_npc_id'] = $this->resolveByName(Npc::class, 'real_name', $rawRow['assisting_npc_id'] ?? null);
        $data['required_quest_id'] = $this->resolveQuestReference($nameToId, $rawRow['required_quest_id'] ?? null);
        $data['parent_quest_id'] = $this->resolveQuestReference($nameToId, $rawRow['parent_quest_id'] ?? null);

        if (is_null($data['npc_id']) || $data['npc_id'] === false) {
            return null;
        }

        $relationFields = ['item_id', 'raid_id', 'reward_item', 'secondary_required_item', 'access_to_map_id', 'faction_game_map_id', 'unlocks_passive_id', 'assisting_npc_id', 'required_quest_id', 'parent_quest_id'];

        foreach ($relationFields as $field) {
            if ($data[$field] === false) {
                return null;
            }
        }

        $chainResult = $this->resolveRequiredQuestChain($nameToId, $rawRow['required_quest_chain'] ?? null);

        if ($chainResult === false) {
            return null;
        }

        $data['required_quest_chain'] = $chainResult;

        // `is_parent` is compatibility state derived from the actual resulting hierarchy after every
        // row writes (see `writeRows()`); the workbook's own `is_parent` cell is never trusted.
        unset($data['is_parent']);

        if (! $this->hasValidManagedFields($data)) {
            return null;
        }

        unset($data['id']);

        return $this->questService->normalize($data);
    }

    /**
     * Validate the managed Quest workbook field contract.
     */
    private function hasValidManagedFields(array $data): bool
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'before_completion_description' => 'nullable|string',
            'after_completion_description' => 'nullable|string',
            'reincarnated_times' => 'nullable|integer|min:0',
            'required_faction_level' => 'nullable|integer|min:0',
            'required_fame_level' => 'nullable|integer|min:0',
            'gold_cost' => 'nullable|integer|min:0',
            'gold_dust_cost' => 'nullable|integer|min:0',
            'shard_cost' => 'nullable|integer|min:0',
            'copper_coin_cost' => 'nullable|integer|min:0',
            'reward_gold' => 'nullable|integer|min:0',
            'reward_gold_dust' => 'nullable|integer|min:0',
            'reward_shards' => 'nullable|integer|min:0',
            'reward_xp' => 'nullable|integer|min:0',
            'unlocks_skill' => 'required|boolean',
            'only_for_event' => ['nullable', 'integer', Rule::in(array_keys(EventType::getOptionsForSelect()))],
            'unlocks_skill_type' => ['nullable', 'integer', Rule::enum(SkillTypeValue::class)],
            'unlocks_feature' => ['nullable', 'integer', Rule::enum(FeatureType::class)],
        ]);

        return ! $validator->fails();
    }

    /**
     * Resolve a single Quest-name reference against the complete workbook Quest-name graph.
     */
    private function resolveQuestReference(array $nameToId, ?string $value): int|false|null
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        return $nameToId[$value] ?? false;
    }

    /**
     * Resolve the ordered Quest names in a required Quest chain to ids.
     */
    private function resolveRequiredQuestChain(array $nameToId, ?string $value): array|false|null
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $names = array_filter(array_map('trim', explode(',', $value)), fn (string $name) => $name !== '');
        $ids = [];

        foreach ($names as $name) {
            if (! array_key_exists($name, $nameToId)) {
                return false;
            }

            $ids[] = $nameToId[$name];
        }

        return $ids;
    }

    /**
     * Resolve a related record's id by its display-name column.
     */
    private function resolveByName(string $modelClass, string $nameColumn, ?string $value): int|false|null
    {
        if (is_null($value) || trim($value) === '') {
            return null;
        }

        $record = $modelClass::where($nameColumn, $value)->first();

        if (is_null($record)) {
            return false;
        }

        return $record->id;
    }

    /**
     * Apply Quest workbook defaults and normalize finite-domain cells.
     */
    private function applyDefaults(array $rawRow): array
    {
        $rawRow['unlocks_skill'] = filter_var($rawRow['unlocks_skill'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach (['only_for_event', 'unlocks_skill_type', 'unlocks_feature'] as $key) {
            if (($rawRow[$key] ?? null) === '') {
                $rawRow[$key] = null;
            }
        }

        return $rawRow;
    }
}
