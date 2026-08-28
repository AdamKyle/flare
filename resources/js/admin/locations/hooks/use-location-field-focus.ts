import { useEffect } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';

export const useLocationFieldFocus = (
  pendingFieldId: string | null,
  clearPendingFieldId: () => void
): void => {
  useEffect(() => {
    if (!pendingFieldId) {
      return;
    }

    focusAndScrollToField(pendingFieldId);
    clearPendingFieldId();
  }, [pendingFieldId, clearPendingFieldId]);
};
