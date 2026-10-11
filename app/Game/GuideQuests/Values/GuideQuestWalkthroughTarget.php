<?php

namespace App\Game\GuideQuests\Values;

enum GuideQuestWalkthroughTarget: string
{
    case MANUAL_FIGHT_INITIATE = 'manual-fight-initiate';
    case MANUAL_FIGHT_ACTIONS = 'manual-fight-actions';
    case BATTLE_MESSAGES = 'battle-messages';
    case SERVER_MESSAGES_TAB = 'server-messages-tab';
    case SERVER_MESSAGES_PANEL = 'server-messages-panel';
    case SERVER_MESSAGE_QUEST_ITEM = 'server-message-quest-item';
    case SERVER_MESSAGE_ITEM = 'server-message-item';
    case QUEST_ITEM_DETAILS = 'quest-item-details';
    case SIDE_PEEK_CLOSE = 'side-peek-close';
    case ITEM_DETAILS = 'item-details';
    case ITEM_STATS = 'item-stats';
    case ITEM_AFFIXES = 'item-affixes';
    case ITEM_AFFIX_DETAILS = 'item-affix-details';
    case ITEM_AFFIX_CLOSE = 'item-affix-close';
    case ITEM_ACTIONS = 'item-actions';
    case ITEM_EQUIP_ACTION = 'item-equip-action';
    case ITEM_EQUIP_COMPARISON = 'item-equip-comparison';
    case ITEM_EQUIP_OPTIONS = 'item-equip-options';
    case ITEM_EQUIP_CONFIRM = 'item-equip-confirm';
    case ITEM_EQUIPPED_STATE = 'item-equipped-state';
    case EXPLORATION_OPEN = 'exploration-open';
    case EXPLORATION_MONSTER = 'exploration-monster';
    case EXPLORATION_DURATION = 'exploration-duration';
    case EXPLORATION_ATTACK_TYPE = 'exploration-attack-type';
    case EXPLORATION_BEGIN = 'exploration-begin';
    case EXPLORATION_LIVE_PANEL = 'exploration-live-panel';
    case EXPLORATION_MESSAGES_TAB = 'exploration-messages-tab';
    case EXPLORATION_PROGRESS = 'exploration-progress';
    case EXPLORATION_CURRENCIES = 'exploration-currencies';
    case EXPLORATION_COMBAT_SUMMARY = 'exploration-combat-summary';
    case CHARACTER_OPEN = 'character-open';
    case CHARACTER_CARD_OVERVIEW = 'character-card-overview';
    case CHARACTER_MANAGE_INVENTORY = 'character-manage-inventory';
    case CHARACTER_MORE_DETAILS = 'character-more-details';
    case CHARACTER_SHEET_OVERVIEW = 'character-sheet-overview';
    case CHARACTER_SKILLS = 'character-skills';
    case SKILLS_OVERVIEW = 'skills-overview';
    case TRAINING_SKILLS_TAB = 'training-skills-tab';
    case ACCURACY_SKILL = 'accuracy-skill';
    case SKILL_TRAINING_DETAILS = 'skill-training-details';
    case SKILL_FACTS = 'skill-facts';
    case SKILL_TRAINING_SECTION = 'skill-training-section';
    case SKILL_XP_SACRIFICE_HELP = 'skill-xp-sacrifice-help';
    case SKILL_TRAINING_PERCENTAGE = 'skill-training-percentage';
    case SKILL_TRAINING_SUBMIT = 'skill-training-submit';
    case SKILL_ITEMS_AFFECTING = 'skill-items-affecting';
    case SKILL_QUEST_ITEM = 'skill-quest-item';
    case SKILL_QUEST_ITEM_DETAILS = 'skill-quest-item-details';
    case SKILL_QUEST_ITEM_CLOSE = 'skill-quest-item-close';
    case MAP_OPEN = 'map-open';
    case MAP_FACTIONS = 'map-factions';
    case FACTIONS_OVERVIEW = 'factions-overview';
    case MAP_CLOSE = 'map-close';
    case SURFACE_FACTION = 'surface-faction';
    case FACTION_DETAIL_OVERVIEW = 'faction-detail-overview';
    case FACTION_PLEDGE = 'faction-pledge';
    case FACTION_LOYALTY_OPEN = 'faction-loyalty-open';
    case FACTION_LOYALTY_NPC = 'faction-loyalty-npc';
    case FACTION_ASSIST_NPC = 'faction-assist-npc';
    case SERVER_MESSAGE_FACTION_BONUS = 'server-message-faction-bonus';
    case CHARACTER_INVENTORY = 'character-inventory';
    case CHARACTER_INVENTORY_CLOSE = 'character-inventory-close';
    case INVENTORY_BACKPACK = 'inventory-backpack';
    case INVENTORY_BACKPACK_CONTENT = 'inventory-backpack-content';
    case INVENTORY_EQUIPPED_ITEM = 'inventory-equipped-item';
    case INVENTORY_SETS = 'inventory-sets';
    case INVENTORY_USABLE = 'inventory-usable';
    case INVENTORY_GEMS = 'inventory-gems';
    case CHARACTER_MODIFIED_STAT = 'character-modified-stat';
    case CHARACTER_STAT_BREAKDOWN = 'character-stat-breakdown';

    /**
     * Return the Admin-facing target label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MANUAL_FIGHT_INITIATE => 'Initiate Fight',
            self::MANUAL_FIGHT_ACTIONS => 'Manual Fight Actions',
            self::BATTLE_MESSAGES => 'Battle Messages',
            self::SERVER_MESSAGES_TAB => 'Server Messages Tab',
            self::SERVER_MESSAGES_PANEL => 'Server Messages Panel',
            self::SERVER_MESSAGE_QUEST_ITEM => 'Quest Item Server Message',
            self::SERVER_MESSAGE_ITEM => 'Equipment Item Server Message',
            self::QUEST_ITEM_DETAILS => 'Quest Item Details',
            self::SIDE_PEEK_CLOSE => 'Side Peek Close',
            self::ITEM_DETAILS => 'Item Details',
            self::ITEM_STATS => 'Item Stats',
            self::ITEM_AFFIXES => 'Item Enchantments',
            self::ITEM_AFFIX_DETAILS => 'Item Enchantment Details',
            self::ITEM_AFFIX_CLOSE => 'Close Item Enchantment Details',
            self::ITEM_ACTIONS => 'Item Actions',
            self::ITEM_EQUIP_ACTION => 'Equip Item Action',
            self::ITEM_EQUIP_COMPARISON => 'Equipment Comparison',
            self::ITEM_EQUIP_OPTIONS => 'Equip Item Options',
            self::ITEM_EQUIP_CONFIRM => 'Equip Item Confirmation',
            self::ITEM_EQUIPPED_STATE => 'Equipped Item State',
            self::EXPLORATION_OPEN => 'Open Exploration',
            self::EXPLORATION_MONSTER => 'Exploration Monster',
            self::EXPLORATION_DURATION => 'Exploration Duration',
            self::EXPLORATION_ATTACK_TYPE => 'Exploration Attack Type',
            self::EXPLORATION_BEGIN => 'Begin Exploration',
            self::EXPLORATION_LIVE_PANEL => 'Live Exploration Panel',
            self::EXPLORATION_MESSAGES_TAB => 'Exploration Messages Tab',
            self::EXPLORATION_PROGRESS => 'Exploration Progress',
            self::EXPLORATION_CURRENCIES => 'Exploration Currencies',
            self::EXPLORATION_COMBAT_SUMMARY => 'Exploration Combat Summary',
            self::CHARACTER_OPEN => 'Open Character',
            self::CHARACTER_CARD_OVERVIEW => 'Character Card Overview',
            self::CHARACTER_MANAGE_INVENTORY => 'Character Manage Inventory',
            self::CHARACTER_MORE_DETAILS => 'Character See More Details',
            self::CHARACTER_SHEET_OVERVIEW => 'Character Sheet Overview',
            self::CHARACTER_SKILLS => 'Character Skills',
            self::SKILLS_OVERVIEW => 'Skills Overview',
            self::TRAINING_SKILLS_TAB => 'Training Skills Tab',
            self::ACCURACY_SKILL => 'Accuracy Skill',
            self::SKILL_TRAINING_DETAILS => 'Skill Training Details',
            self::SKILL_FACTS => 'Skill Facts',
            self::SKILL_TRAINING_SECTION => 'Skill Training Section',
            self::SKILL_XP_SACRIFICE_HELP => 'Skill XP Sacrifice Help',
            self::SKILL_TRAINING_PERCENTAGE => 'Skill Training Percentage',
            self::SKILL_TRAINING_SUBMIT => 'Start Skill Training',
            self::SKILL_ITEMS_AFFECTING => 'Items Affecting Skill',
            self::SKILL_QUEST_ITEM => 'Skill Quest Item',
            self::SKILL_QUEST_ITEM_DETAILS => 'Skill Quest Item Details',
            self::SKILL_QUEST_ITEM_CLOSE => 'Close Skill Quest Item Details',
            self::MAP_OPEN => 'Open Map',
            self::MAP_FACTIONS => 'Map Factions',
            self::FACTIONS_OVERVIEW => 'Factions Overview',
            self::MAP_CLOSE => 'Close Map',
            self::SURFACE_FACTION => 'Surface Faction',
            self::FACTION_DETAIL_OVERVIEW => 'Faction Detail Overview',
            self::FACTION_PLEDGE => 'Pledge to Faction',
            self::FACTION_LOYALTY_OPEN => 'Open Faction Loyalty',
            self::FACTION_LOYALTY_NPC => 'Faction Loyalty NPC',
            self::FACTION_ASSIST_NPC => 'Assist Faction NPC',
            self::SERVER_MESSAGE_FACTION_BONUS => 'Faction Bonus Server Message',
            self::CHARACTER_INVENTORY => 'Character Inventory',
            self::CHARACTER_INVENTORY_CLOSE => 'Close Character Inventory',
            self::INVENTORY_BACKPACK => 'Inventory Backpack',
            self::INVENTORY_BACKPACK_CONTENT => 'Backpack Content',
            self::INVENTORY_EQUIPPED_ITEM => 'Equipped Inventory Item',
            self::INVENTORY_SETS => 'Inventory Sets',
            self::INVENTORY_USABLE => 'Usable Inventory',
            self::INVENTORY_GEMS => 'Gem Bag',
            self::CHARACTER_MODIFIED_STAT => 'Modified Character Stat',
            self::CHARACTER_STAT_BREAKDOWN => 'Character Stat Breakdown',
        };
    }

    /**
     * Return every target as an Admin select option.
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn (self $target): array => [
            'value' => $target->value,
            'label' => $target->label(),
        ])->all();
    }
}
