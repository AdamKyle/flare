export enum GameMapApiUrls {
  LIST = '/admin/game-maps',
  SHOW = '/admin/game-maps/{gameMap}',
  EDITOR = '/admin/game-maps/{gameMap}/editor',
  EDIT = '/admin/game-maps/{gameMap}/edit',
  OPTIONS = '/admin/game-maps/options',
  IMPORT = '/admin/game-maps/import',
  RELATED_LOCATIONS = '/admin/game-maps/{gameMap}/related-locations',
  RELATED_NPCS = '/admin/game-maps/{gameMap}/related-npcs',
  RELATED_MONSTERS = '/admin/game-maps/{gameMap}/related-monsters',
  RELATED_QUESTS = '/admin/game-maps/{gameMap}/related-quests',
  RELATED_QUEST_ITEMS = '/admin/game-maps/{gameMap}/related-quest-items',
}
