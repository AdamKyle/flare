import { useEffect } from 'react';

export default function useBatchCraftingRefresh(refresh: () => void) {
  useEffect(() => {
    refresh();
  }, [refresh]);
}
