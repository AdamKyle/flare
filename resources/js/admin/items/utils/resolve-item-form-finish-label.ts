export const resolveItemFormFinishLabel = (
  saving: boolean,
  itemId: number | null
): string => {
  if (saving) {
    return 'Saving…';
  }

  return itemId === null ? 'Create Item' : 'Save Item';
};
