export enum NpcApiUrls {
  OPTIONS = '/admin/game-maps/{gameMap}/npcs/options',
  SHOW = '/admin/game-maps/{gameMap}/npcs/{npc}',
  STORE = '/admin/game-maps/{gameMap}/npcs',
  UPDATE = SHOW,
  MOVE = '/admin/game-maps/{gameMap}/npcs/{npc}/position',
  LIST = '/admin/npcs',
  DETAIL = '/admin/npcs/{npc}',
  QUESTS = '/admin/npcs/{npc}/quests',
  REWARD_ITEMS = '/admin/npcs/{npc}/reward-items',
  IMPORT = '/admin/npcs/import',
}
