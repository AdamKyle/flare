import React, { ReactNode } from 'react';

import SkillCard from './skill-card';
import SkillCardListProps from './types/skill-card-list-props';
import CharacterSkillDefinition from '../api/definitions/character-skill-definition';
import { SKILL_LIST_BATCH_SIZE } from '../constants/skill-list-constants';

import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const SkillCardList = ({
  skills,
  empty_message: emptyMessage,
  on_open_skill: onOpenSkill,
}: SkillCardListProps): ReactNode => {
  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveList({
      total_items: skills.length,
      batch_size: SKILL_LIST_BATCH_SIZE,
      initial_count: SKILL_LIST_BATCH_SIZE,
    });

  const visibleSkills = skills.slice(0, visibleCount);

  const renderSkill = (skill: CharacterSkillDefinition) => (
    <li key={skill.id}>
      <SkillCard skill={skill} on_open={onOpenSkill} />
    </li>
  );

  if (skills.length === 0) {
    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">{emptyMessage}</p>
    );
  }

  return (
    <InfiniteScroll height_class="max-h-96" handle_scroll={handleScroll}>
      <ul className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        {visibleSkills.map(renderSkill)}
      </ul>
      <p className="sr-only" role="status" aria-live="polite">
        Showing {visibleSkills.length} of {skills.length} skills.
      </p>
    </InfiniteScroll>
  );
};

export default SkillCardList;
