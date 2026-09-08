export const resolveGameMapFormFinishLabel = (
  saving: boolean,
  game_map_id: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  if (game_map_id === null) {
    return 'Create Game Map';
  }

  return 'Save Changes';
};
