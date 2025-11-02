import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import GemSlot from './gem-slot';
import GemListProps from './types/gem-list-props';
import BaseGemDetails from '../../../../api-definitions/items/base-gem-details';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const GemList = ({
  gems,
  on_scroll_to_end,
  on_view_gem,
}: GemListProps): ReactNode => {
  const renderGemSlots = () => {
    if (isEmpty(gems)) {
      return (
        <div className="py-4 text-center">
          You don't have any gems. These can be crafted and then socketed into
          your gear to provide elemental immunity to certain monsters as well as
          deal elemental attacks
        </div>
      );
    }

    return gems.map((gem: BaseGemDetails) => (
      <GemSlot key={gem.slot_id} gem_slot={gem} on_view_gem={on_view_gem} />
    ));
  };

  return (
    <div className="h-full w-full text-gray-800 dark:text-gray-200">
      <InfiniteScroll handle_scroll={on_scroll_to_end} additional_css={'my-2'}>
        {renderGemSlots()}
      </InfiniteScroll>
    </div>
  );
};

export default GemList;
