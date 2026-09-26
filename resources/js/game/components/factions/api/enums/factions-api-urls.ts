export enum FactionsApiUrls {
  FACTIONS = '/character-sheet/{character}/factions',
  PLEDGE = '/faction-loyalty/pledge/{character}/{faction}',
  REMOVE_PLEDGE = '/faction-loyalty/remove-pledge/{character}/{faction}',
  FACTION_LOYALTY = '/faction-loyalty/{character}',
  ASSIST_NPC = '/faction-loyalty/assist/{character}/{factionLoyaltyNpc}',
  STOP_ASSISTING_NPC = '/faction-loyalty/stop-assisting/{character}/{factionLoyaltyNpc}',
  START_AUTOMATION = '/faction-loyalty-automation/{character}/start',
  STOP_AUTOMATION = '/faction-loyalty-automation/{character}/stop',
  DISMISS_AUTOMATION_WARNING = '/faction-loyalty-automation/{character}/warning/dismiss',
}
