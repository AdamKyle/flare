import React from 'react';

export interface UseProgressiveActiveBoonsParams {
  total_items: number;
  reset_key?: string | number;
}

export default interface UseProgressiveActiveBoonsDefinition {
  visible_count: number;
  handle_scroll: (event: React.UIEvent<HTMLDivElement>) => void;
  has_more: boolean;
}
