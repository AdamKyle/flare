export const GuideTargets = {
  MANUAL_FIGHT_INITIATE: 'manual-fight-initiate',
  MANUAL_FIGHT_ACTIONS: 'manual-fight-actions',
  BATTLE_MESSAGES: 'battle-messages',
  SERVER_MESSAGE_ITEM: 'server-message-item',
  ITEM_STATS: 'item-stats',
  ITEM_EQUIP_ACTION: 'item-equip-action',
} as const;

export type GuideTarget = (typeof GuideTargets)[keyof typeof GuideTargets];

const stateDependentTargets = new Set<GuideTarget>([
  GuideTargets.MANUAL_FIGHT_ACTIONS,
]);

export const isStateDependentGuideTarget = (target: GuideTarget): boolean =>
  stateDependentTargets.has(target);
