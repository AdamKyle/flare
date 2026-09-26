import React from 'react';

export default interface UseProgressiveListDefinition {
  visible_count: number;
  handle_scroll: (event: React.UIEvent<HTMLDivElement>) => void;
  has_more: boolean;
}
