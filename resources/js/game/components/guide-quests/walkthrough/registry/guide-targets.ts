import { GuideWalkthroughInteraction } from '../enums/guide-walkthrough-interaction';
import { GuideWalkthroughInteractionSignal } from '../enums/guide-walkthrough-interaction-signal';
import GuideTargetInteractionDefinition from '../types/guide-target-interaction-definition';

export const GuideTargets = {
  MANUAL_FIGHT_INITIATE: 'manual-fight-initiate',
  MANUAL_FIGHT_ACTIONS: 'manual-fight-actions',
  BATTLE_MESSAGES: 'battle-messages',
  SERVER_MESSAGES_TAB: 'server-messages-tab',
  SERVER_MESSAGES_PANEL: 'server-messages-panel',
  SERVER_MESSAGE_QUEST_ITEM: 'server-message-quest-item',
  SERVER_MESSAGE_ITEM: 'server-message-item',
  QUEST_ITEM_DETAILS: 'quest-item-details',
  SIDE_PEEK_CLOSE: 'side-peek-close',
  ITEM_DETAILS: 'item-details',
  ITEM_STATS: 'item-stats',
  ITEM_AFFIXES: 'item-affixes',
  ITEM_AFFIX_DETAILS: 'item-affix-details',
  ITEM_AFFIX_CLOSE: 'item-affix-close',
  ITEM_ACTIONS: 'item-actions',
  ITEM_EQUIP_ACTION: 'item-equip-action',
  ITEM_EQUIP_COMPARISON: 'item-equip-comparison',
  ITEM_EQUIP_OPTIONS: 'item-equip-options',
  ITEM_EQUIP_CONFIRM: 'item-equip-confirm',
  ITEM_EQUIPPED_STATE: 'item-equipped-state',
  EXPLORATION_OPEN: 'exploration-open',
  EXPLORATION_MONSTER: 'exploration-monster',
  EXPLORATION_DURATION: 'exploration-duration',
  EXPLORATION_ATTACK_TYPE: 'exploration-attack-type',
  EXPLORATION_BEGIN: 'exploration-begin',
  EXPLORATION_LIVE_PANEL: 'exploration-live-panel',
  EXPLORATION_MESSAGES_TAB: 'exploration-messages-tab',
  EXPLORATION_PROGRESS: 'exploration-progress',
  EXPLORATION_CURRENCIES: 'exploration-currencies',
  EXPLORATION_COMBAT_SUMMARY: 'exploration-combat-summary',
  CHARACTER_OPEN: 'character-open',
  CHARACTER_CARD_OVERVIEW: 'character-card-overview',
  CHARACTER_MANAGE_INVENTORY: 'character-manage-inventory',
  CHARACTER_MORE_DETAILS: 'character-more-details',
  CHARACTER_SHEET_OVERVIEW: 'character-sheet-overview',
  CHARACTER_SKILLS: 'character-skills',
  SKILLS_OVERVIEW: 'skills-overview',
  TRAINING_SKILLS_TAB: 'training-skills-tab',
  ACCURACY_SKILL: 'accuracy-skill',
  SKILL_TRAINING_DETAILS: 'skill-training-details',
  SKILL_FACTS: 'skill-facts',
  SKILL_TRAINING_SECTION: 'skill-training-section',
  SKILL_XP_SACRIFICE_HELP: 'skill-xp-sacrifice-help',
  SKILL_TRAINING_PERCENTAGE: 'skill-training-percentage',
  SKILL_TRAINING_SUBMIT: 'skill-training-submit',
  SKILL_ITEMS_AFFECTING: 'skill-items-affecting',
  SKILL_QUEST_ITEM: 'skill-quest-item',
  SKILL_QUEST_ITEM_DETAILS: 'skill-quest-item-details',
  SKILL_QUEST_ITEM_CLOSE: 'skill-quest-item-close',
  MAP_OPEN: 'map-open',
  MAP_FACTIONS: 'map-factions',
  FACTIONS_OVERVIEW: 'factions-overview',
  MAP_CLOSE: 'map-close',
  SURFACE_FACTION: 'surface-faction',
  FACTION_DETAIL_OVERVIEW: 'faction-detail-overview',
  FACTION_PLEDGE: 'faction-pledge',
  FACTION_LOYALTY_OPEN: 'faction-loyalty-open',
  FACTION_LOYALTY_NPC: 'faction-loyalty-npc',
  FACTION_ASSIST_NPC: 'faction-assist-npc',
  SERVER_MESSAGE_FACTION_BONUS: 'server-message-faction-bonus',
  CHARACTER_INVENTORY: 'character-inventory',
  CHARACTER_INVENTORY_CLOSE: 'character-inventory-close',
  INVENTORY_BACKPACK: 'inventory-backpack',
  INVENTORY_BACKPACK_CONTENT: 'inventory-backpack-content',
  INVENTORY_EQUIPPED_ITEM: 'inventory-equipped-item',
  INVENTORY_SETS: 'inventory-sets',
  INVENTORY_USABLE: 'inventory-usable',
  INVENTORY_GEMS: 'inventory-gems',
  CHARACTER_MODIFIED_STAT: 'character-modified-stat',
  CHARACTER_STAT_BREAKDOWN: 'character-stat-breakdown',
} as const;

export type GuideTarget = (typeof GuideTargets)[keyof typeof GuideTargets];

const targetInteractions: Partial<
  Record<GuideTarget, GuideTargetInteractionDefinition>
> = {
  [GuideTargets.EXPLORATION_OPEN]: {
    interaction: GuideWalkthroughInteraction.SIMPLE_CLICK,
    skip_when_target_missing: true,
  },
  [GuideTargets.EXPLORATION_MONSTER]: {
    interaction: GuideWalkthroughInteraction.OPEN_AND_SELECT,
    completion_signal: GuideWalkthroughInteractionSignal.SELECTED,
    popover_side: 'top',
    popover_align: 'center',
  },
  [GuideTargets.EXPLORATION_DURATION]: {
    interaction: GuideWalkthroughInteraction.OPEN_AND_SELECT,
    completion_signal: GuideWalkthroughInteractionSignal.SELECTED,
    popover_side: 'top',
    popover_align: 'center',
  },
  [GuideTargets.EXPLORATION_ATTACK_TYPE]: {
    interaction: GuideWalkthroughInteraction.OPEN_AND_SELECT,
    completion_signal: GuideWalkthroughInteractionSignal.SELECTED,
    popover_side: 'top',
    popover_align: 'center',
  },
  [GuideTargets.SKILL_TRAINING_PERCENTAGE]: {
    interaction: GuideWalkthroughInteraction.OPEN_AND_SELECT,
    completion_signal: GuideWalkthroughInteractionSignal.SELECTED,
    popover_side: 'top',
    popover_align: 'center',
  },
  [GuideTargets.ITEM_ACTIONS]: {
    interaction: GuideWalkthroughInteraction.OPEN_AND_ACTION,
    completion_signal: GuideWalkthroughInteractionSignal.OPENED,
    popover_side: 'left',
    popover_align: 'start',
    cleanup_on_exit: true,
  },
};

export const resolveGuideTargetInteraction = (
  target: GuideTarget
): GuideTargetInteractionDefinition | null =>
  targetInteractions[target] ?? null;

const stateDependentTargets = new Set<GuideTarget>([
  GuideTargets.MANUAL_FIGHT_ACTIONS,
  GuideTargets.SKILL_TRAINING_SECTION,
  GuideTargets.SKILL_TRAINING_PERCENTAGE,
  GuideTargets.SKILL_XP_SACRIFICE_HELP,
  GuideTargets.SKILL_TRAINING_SUBMIT,
  GuideTargets.CHARACTER_MANAGE_INVENTORY,
]);

export const isStateDependentGuideTarget = (target: GuideTarget): boolean =>
  stateDependentTargets.has(target);

const asyncTargets = new Set<GuideTarget>([
  GuideTargets.SERVER_MESSAGES_PANEL,
  GuideTargets.QUEST_ITEM_DETAILS,
  GuideTargets.ITEM_DETAILS,
  GuideTargets.ITEM_AFFIX_DETAILS,
  GuideTargets.ITEM_EQUIP_COMPARISON,
  GuideTargets.ITEM_EQUIP_OPTIONS,
  GuideTargets.ITEM_EQUIP_CONFIRM,
  GuideTargets.ITEM_EQUIPPED_STATE,
  GuideTargets.EXPLORATION_LIVE_PANEL,
  GuideTargets.EXPLORATION_PROGRESS,
  GuideTargets.EXPLORATION_CURRENCIES,
  GuideTargets.EXPLORATION_COMBAT_SUMMARY,
  GuideTargets.CHARACTER_CARD_OVERVIEW,
  GuideTargets.CHARACTER_MORE_DETAILS,
  GuideTargets.CHARACTER_SHEET_OVERVIEW,
  GuideTargets.SKILLS_OVERVIEW,
  GuideTargets.ACCURACY_SKILL,
  GuideTargets.SKILL_TRAINING_DETAILS,
  GuideTargets.SKILL_FACTS,
  GuideTargets.SKILL_TRAINING_PERCENTAGE,
  GuideTargets.SKILL_TRAINING_SUBMIT,
  GuideTargets.SKILL_ITEMS_AFFECTING,
  GuideTargets.SKILL_QUEST_ITEM,
  GuideTargets.SKILL_QUEST_ITEM_DETAILS,
  GuideTargets.SKILL_QUEST_ITEM_CLOSE,
  GuideTargets.FACTIONS_OVERVIEW,
  GuideTargets.FACTION_DETAIL_OVERVIEW,
  GuideTargets.MAP_CLOSE,
  GuideTargets.SURFACE_FACTION,
  GuideTargets.FACTION_PLEDGE,
  GuideTargets.FACTION_LOYALTY_OPEN,
  GuideTargets.FACTION_LOYALTY_NPC,
  GuideTargets.FACTION_ASSIST_NPC,
  GuideTargets.SERVER_MESSAGE_FACTION_BONUS,
  GuideTargets.INVENTORY_BACKPACK,
  GuideTargets.INVENTORY_BACKPACK_CONTENT,
  GuideTargets.CHARACTER_INVENTORY_CLOSE,
  GuideTargets.INVENTORY_EQUIPPED_ITEM,
  GuideTargets.INVENTORY_SETS,
  GuideTargets.INVENTORY_USABLE,
  GuideTargets.INVENTORY_GEMS,
  GuideTargets.CHARACTER_STAT_BREAKDOWN,
]);

export const isAsyncGuideTarget = (target: GuideTarget): boolean =>
  asyncTargets.has(target);
