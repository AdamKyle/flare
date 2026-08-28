export enum LocationApiUrls {
  OPTIONS = '/admin/game-maps/{gameMap}/locations/options',
  SHOW = '/admin/game-maps/{gameMap}/locations/{location}',
  STORE = '/admin/game-maps/{gameMap}/locations',
  UPDATE = SHOW,
  MOVE = '/admin/game-maps/{gameMap}/locations/{location}/position',
}
