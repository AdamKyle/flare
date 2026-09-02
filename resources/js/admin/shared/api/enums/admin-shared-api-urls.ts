/**
 * URLs shared across multiple Admin feature modules. The Game Map filter
 * options endpoint is Quest's existing `browse-options` route — it already
 * returns exactly the default Game Map id and ordered Game Map list every
 * Admin list's Game Map filter needs, so no new backend route is added.
 */
export enum AdminSharedApiUrls {
  GAME_MAP_FILTER_OPTIONS = '/admin/quests/browse-options',
}
