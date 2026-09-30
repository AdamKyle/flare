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
} as const;

export type GuideTarget = (typeof GuideTargets)[keyof typeof GuideTargets];

const stateDependentTargets = new Set<GuideTarget>([
  GuideTargets.MANUAL_FIGHT_ACTIONS,
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
]);

export const isAsyncGuideTarget = (target: GuideTarget): boolean =>
  asyncTargets.has(target);
