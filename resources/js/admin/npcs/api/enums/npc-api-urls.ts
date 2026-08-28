export enum NpcApiUrls {
  OPTIONS = '/admin/game-maps/{gameMap}/npcs/options',
  SHOW = '/admin/game-maps/{gameMap}/npcs/{npc}',
  STORE = '/admin/game-maps/{gameMap}/npcs',
  UPDATE = SHOW,
  MOVE = '/admin/game-maps/{gameMap}/npcs/{npc}/position',
}
