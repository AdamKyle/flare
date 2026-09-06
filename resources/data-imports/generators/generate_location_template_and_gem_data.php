<?php

require __DIR__.'/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$basePath = dirname(__DIR__);

$locationTemplateHeaders = ['name', 'description', 'type', 'is_port', 'can_players_enter'];
$mapGemHeaders = [
    'id',
    'name',
    'description',
    'game_map_name',
    'crafting_skill_names',
    'character_xp_bonus_range',
    'character_class_rank_xp_bonus_range',
    'kingdom_passive_training_reduction_range',
    'gold_gain_range',
    'gold_dust_gain_range',
    'shards_gain_range',
    'copper_coin_gain_range',
    'character_class_specialty_xp_gain_range',
    'crafting_skill_bonus_range',
    'item_drop_chance_increase_range',
    'unique_item_drop_chance_increase_range',
    'mythic_item_drop_chance_increase_range',
    'cosmic_item_drop_chance_increase_range',
    'character_power_reduction_range',
    'enemy_strength_increase_range',
    'enemy_healing_increase_range',
    'enemy_spell_evasion_range',
    'enemy_affix_resistance_range',
    'enemy_entrancing_chance_range',
    'enemy_devouring_light_chance_range',
    'enemy_devouring_darkness_chance_range',
    'enemy_ambush_chance_range',
    'enemy_ambush_resistance_range',
    'enemy_counter_chance_range',
    'enemy_counter_resistance_range',
    'enemy_quest_item_drop_chance_increase_range',
    'monster_xp_increase_range',
    'monster_gold_drop_increase_range',
    'monster_atonement',
    'monster_atonement_range',
];
$locationGemHeaders = array_values(array_filter(
    array_merge(array_slice($mapGemHeaders, 0, 4), ['location_name'], array_slice($mapGemHeaders, 4)),
    fn (string $header): bool => $header !== 'character_power_reduction_range',
));

$mapGemMaps = [
    'Shadow Plane',
    'Hell',
    'Purgatory',
    'Twisted Memories',
    'Delusional Memories',
    'The Ice Plane',
];

$locations = [
    ['Shadow Caves', 'Surface'],
    ['Labyrinth Maze', 'Labyrinth'],
    ['Dungeons of Valifore', 'Dungeons'],
    ['Wrecked Ship', 'Shadow Plane'],
    ['Satans Cage', 'Hell'],
    ['Bandits Twisted Arm Port', 'Twisted Memories'],
    ['Church of God', 'Twisted Memories'],
    ['Emerald Mining Town', 'Twisted Memories'],
    ['Twisted Memorial Crypt', 'Twisted Memories'],
    ['Twisted grave site', 'Twisted Memories'],
    ['Abandoned Village', 'The Ice Plane'],
    ['Banshee Fields of Tomorrow', 'The Ice Plane'],
    ['Bloody Snowman', 'The Ice Plane'],
    ['Broken Forest Road', 'The Ice Plane'],
    ['Frozen Pet Cemetary', 'The Ice Plane'],
    ['Frozen Queens Bank', 'The Ice Plane'],
    ['The Frozen Wreck', 'The Ice Plane'],
    ['Abandonded Chapel', 'Delusional Memories'],
    ['Delusional Abandoned Gold Mines', 'Delusional Memories'],
    ['Federation Controlled Town', 'Delusional Memories'],
    ['Underwater Caves', 'Surface'],
    ['Gold Mine', 'Shadow Plane'],
    ['Tear in the Fabric of Time', 'Hell'],
    ['Twisted Dimensional Gate', 'Hell'],
    ['Purgatories Dungeons', 'Purgatory'],
    ['Purgatory Smiths House', 'Purgatory'],
    ['Cave of Shadows', 'Twisted Memories'],
    ['The Old Church', 'The Ice Plane'],
];

$generationSets = array_merge(
    array_map(fn (string $map): array => [$map, $map], $mapGemMaps),
    $locations,
);

$planeRules = [
    'Shadow Plane' => [
        'general' => [0.08, 0.15],
        'monster' => [0.06, 0.12],
        'weakness' => [0.04, 0.12],
    ],
    'Hell' => [
        'general' => [0.12, 0.20],
        'monster' => [0.18, 0.25],
        'weakness' => [0.20, 0.30],
    ],
    'Purgatory' => [
        'general' => [0.25, 0.38],
        'monster' => [0.30, 0.55],
        'weakness' => [0.28, 0.39],
    ],
    'Twisted Memories' => [
        'general' => [0.35, 0.43],
        'monster' => [0.48, 0.65],
        'weakness' => [0.42, 0.48],
    ],
    // Delusional Memories intentionally uses Twisted Memories values because no explicit separate rule exists.
    'Delusional Memories' => [
        'general' => [0.35, 0.43],
        'monster' => [0.48, 0.65],
        'weakness' => [0.42, 0.48],
    ],
    // The Ice Plane intentionally uses Purgatory values because no explicit separate rule exists.
    'The Ice Plane' => [
        'general' => [0.25, 0.38],
        'monster' => [0.30, 0.55],
        'weakness' => [0.28, 0.39],
    ],
    // Surface, Dungeons, and Labyrinth location gem rows intentionally use Shadow Plane as a baseline.
    'Surface' => [
        'general' => [0.08, 0.15],
        'monster' => [0.06, 0.12],
        'weakness' => [0.04, 0.12],
    ],
    'Dungeons' => [
        'general' => [0.08, 0.15],
        'monster' => [0.06, 0.12],
        'weakness' => [0.04, 0.12],
    ],
    'Labyrinth' => [
        'general' => [0.08, 0.15],
        'monster' => [0.06, 0.12],
        'weakness' => [0.04, 0.12],
    ],
];

$generalFields = [
    'character_xp_bonus_range',
    'character_class_rank_xp_bonus_range',
    'kingdom_passive_training_reduction_range',
    'gold_gain_range',
    'gold_dust_gain_range',
    'shards_gain_range',
    'copper_coin_gain_range',
    'character_class_specialty_xp_gain_range',
    'crafting_skill_bonus_range',
    'item_drop_chance_increase_range',
];
$rarityFields = [
    'unique_item_drop_chance_increase_range',
    'mythic_item_drop_chance_increase_range',
    'cosmic_item_drop_chance_increase_range',
];
$monsterFields = [
    'enemy_strength_increase_range',
    'enemy_healing_increase_range',
    'enemy_spell_evasion_range',
    'enemy_affix_resistance_range',
    'enemy_entrancing_chance_range',
    'enemy_devouring_light_chance_range',
    'enemy_devouring_darkness_chance_range',
    'enemy_ambush_chance_range',
    'enemy_ambush_resistance_range',
    'enemy_counter_chance_range',
    'enemy_counter_resistance_range',
    'enemy_quest_item_drop_chance_increase_range',
    'monster_xp_increase_range',
    'monster_gold_drop_increase_range',
    'monster_atonement_range',
];

function rangeString(array $range, float $multiplier = 1.0): string
{
    return rtrim(rtrim(number_format($range[0] * $multiplier, 4, '.', ''), '0'), '.')
        .'-'.rtrim(rtrim(number_format($range[1] * $multiplier, 4, '.', ''), '0'), '.');
}

function writeSheet(string $path, array $headers, array $rows): void
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    (new Xlsx($spreadsheet))->save($path);
}

function slugName(string $name): string
{
    return trim(preg_replace('/[^A-Za-z0-9]+/', ' ', $name));
}

$regularThemes = [
    'Ash-Bent Hamlet',
    'Federation Scarred Road',
    'Starving Lantern Camp',
    'Drowned Merchant Post',
    'Broken Watch House',
    'Memory-Warped Shrine',
    'Famished Orchard',
    'Grey Survivor Yard',
    'Sundered Well',
    'Cracked Militia Camp',
    'Hollow Bread Market',
    'Worn Stone Causeway',
    'Fallen Cart Road',
    'Cold Prayer Field',
    'Splintered Granary',
    'Mourning Gatehouse',
];
$delveThemes = ['Memory Cellar', 'Buried Choir Mine'];
$specialThemes = [
    'Corrupted Bell Church',
    'Cursed Smith Anvil',
    'Time Tear Obelisk',
    'Childs Memory Gate',
    'Alchemy Rot Site',
    'Federation Wound Stronghold',
    'Enemy Strength Spire',
    'Reality Bent Dungeon',
];
$portThemes = [
    'Ash Wharf',
    'Survivor Ferry',
    'Merchant Gate',
    'Broken Crossing',
    'Federation Checkpoint',
    'Waystation Pier',
];

$locationTemplateRows = [];
$descriptions = [
    'regular' => 'The Childs cracking mind raises %s from hunger, mud, and Federation ruin so travelers can pretend the road still belongs to the living.',
    'delve' => 'Below %s, the Childs thoughts split into stone, cellar dust, and old screams that invite delvers farther from daylight.',
    'special' => '%s exists where the Childs failing memory tears reality around a landmark the Federation could not fully burn away.',
    'port' => '%s keeps a thin trade route open while sailors, refugees, and tired merchants bargain beside water darkened by war.',
];

foreach ($generationSets as $setIndex => [$setName, $planeName]) {
    $prefix = slugName($setName);

    foreach ($regularThemes as $index => $theme) {
        $name = $prefix.' '.$theme;
        $locationTemplateRows[] = [$name, sprintf($descriptions['regular'], $name), 'regular', 0, 1];
    }

    foreach ($delveThemes as $index => $theme) {
        $name = $prefix.' '.$theme;
        $locationTemplateRows[] = [$name, sprintf($descriptions['delve'], $name), 'delve', 0, 1];
    }

    foreach ($specialThemes as $index => $theme) {
        $name = $prefix.' '.$theme;
        $locationTemplateRows[] = [$name, sprintf($descriptions['special'], $name), 'special', 0, 1];
    }

    foreach ($portThemes as $index => $theme) {
        $name = $prefix.' '.$theme;
        $locationTemplateRows[] = [$name, sprintf($descriptions['port'], $name), 'port', 1, 1];
    }
}

$reserveName = 'Admin Reserve Regular Shelter';
$locationTemplateRows[] = [
    $reserveName,
    sprintf($descriptions['regular'], $reserveName).' It is held back as a plain reserve template for future admin map work.',
    'regular',
    0,
    1,
];

$mapRows = [];
foreach ($mapGemMaps as $mapName) {
    $rules = $planeRules[$mapName];
    $row = [
        'id' => '',
        'name' => $mapName.' Gem Profile',
        'description' => 'Gem profile for '.$mapName.' after the Federation devastation and the Childs unstable memory reshaped its rewards.',
        'game_map_name' => $mapName,
        'crafting_skill_names' => '',
        'monster_atonement' => '',
    ];

    foreach ($generalFields as $field) {
        $row[$field] = rangeString($rules['general']);
    }

    foreach ($rarityFields as $field) {
        $row[$field] = '0';
    }

    $row['character_power_reduction_range'] = rangeString($rules['weakness']);

    foreach ($monsterFields as $field) {
        $row[$field] = rangeString($rules['monster']);
    }

    $mapRows[] = array_map(fn (string $header) => $row[$header] ?? '', $mapGemHeaders);
}

$locationRows = [];
foreach ($locations as [$locationName, $planeName]) {
    $rules = $planeRules[$planeName];
    $row = [
        'id' => '',
        'name' => $locationName.' Gem Profile',
        'description' => 'Location gem profile for '.$locationName.' on '.$planeName.', raised five percent above its plane baseline.',
        'game_map_name' => $planeName,
        'location_name' => $locationName,
        'crafting_skill_names' => '',
        'monster_atonement' => '',
    ];

    foreach ($generalFields as $field) {
        $row[$field] = rangeString($rules['general'], 1.05);
    }

    foreach ($rarityFields as $field) {
        $row[$field] = '0';
    }

    foreach ($monsterFields as $field) {
        $row[$field] = rangeString($rules['monster'], 1.05);
    }

    $locationRows[] = array_map(fn (string $header) => $row[$header] ?? '', $locationGemHeaders);
}

writeSheet($basePath.'/Location Templates/location_templates.xlsx', $locationTemplateHeaders, $locationTemplateRows);
writeSheet($basePath.'/World Gems/map-gems.xlsx', $mapGemHeaders, $mapRows);
writeSheet($basePath.'/World Gems/location-gems.xlsx', $locationGemHeaders, $locationRows);
