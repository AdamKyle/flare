import React, { ReactNode } from 'react';

import SkillContributingItemCard from './skill-contributing-item-card';
import SkillItemsAffectingListProps from './types/skill-items-affecting-list-props';
import SkillItemContributionDefinition from '../api/definitions/skill-item-contribution-definition';
import { SKILL_LIST_BATCH_SIZE } from '../constants/skill-list-constants';

import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const SkillItemsAffectingList = ({
  skill_id: skillId,
  items,
  on_open_item: onOpenItem,
}: SkillItemsAffectingListProps): ReactNode => {
  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveList({
      total_items: items.length,
      batch_size: SKILL_LIST_BATCH_SIZE,
      initial_count: SKILL_LIST_BATCH_SIZE,
      reset_key: skillId,
    });

  const visibleItems = items.slice(0, visibleCount);

  const renderItem = (item: SkillItemContributionDefinition) => (
    <li key={`${item.source}-${item.slot_id}`}>
      <SkillContributingItemCard item={item} on_open={onOpenItem} />
    </li>
  );

  const renderItems = (): ReactNode => {
    if (items.length === 0) {
      return (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          None of your equipped items or quest items affect this Skill.
        </p>
      );
    }

    return (
      <InfiniteScroll height_class="max-h-96" handle_scroll={handleScroll}>
        <ul>{visibleItems.map(renderItem)}</ul>
        <p className="sr-only" role="status" aria-live="polite">
          Showing {visibleItems.length} of {items.length} items.
        </p>
      </InfiniteScroll>
    );
  };

  return (
    <section
      aria-label="Items affecting this Skill"
      className="flex flex-col gap-2"
    >
      <h3 className="font-semibold text-gray-900 dark:text-gray-100">
        Items Affecting This Skill
      </h3>
      {renderItems()}
    </section>
  );
};

export default SkillItemsAffectingList;
