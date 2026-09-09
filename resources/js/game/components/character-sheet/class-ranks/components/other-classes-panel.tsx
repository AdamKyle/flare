import React, { ReactNode, useState } from 'react';

import OtherClassCard from './other-class-card';
import OtherClassesPanelProps from './types/other-classes-panel-props';
import { CLASS_BROWSER_INITIAL_COUNT } from '../constants/class-rank-list-constants';
import { ClassRankProgressFilter } from '../enums/class-rank-progress-filter';
import { useProgressiveClassRankList } from '../hooks/use-progressive-class-rank-list';
import { resolveClassRankProgressFilter } from '../utils/resolve-class-rank-progress-filter';
import { sortClassRanksForBrowser } from '../utils/sort-class-ranks-for-browser';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';

const progressFilterOptions: DropdownItem[] = [
  { label: 'Mastered', value: ClassRankProgressFilter.MASTERED },
  { label: 'Has Progress', value: ClassRankProgressFilter.HAS_PROGRESS },
  { label: 'Not Mastered', value: ClassRankProgressFilter.NOT_MASTERED },
];

const OtherClassesPanel = ({
  other_classes: otherClasses,
  on_open_class: onOpenClass,
}: OtherClassesPanelProps): ReactNode => {
  const [progressFilter, setProgressFilter] = useState<ClassRankProgressFilter>(
    ClassRankProgressFilter.ALL
  );

  const sortedClasses = sortClassRanksForBrowser(otherClasses);

  const filteredClasses =
    progressFilter === ClassRankProgressFilter.ALL
      ? sortedClasses
      : sortedClasses.filter(
          (classRank) =>
            resolveClassRankProgressFilter(classRank) === progressFilter
        );

  const resetKey = `${progressFilter}-${filteredClasses
    .map((classRank) => classRank.game_class_id)
    .join('-')}`;

  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveClassRankList({
      total_items: filteredClasses.length,
      reset_key: resetKey,
      initial_count: CLASS_BROWSER_INITIAL_COUNT,
    });

  const visibleClasses = filteredClasses.slice(0, visibleCount);

  const preSelectedItem = progressFilterOptions.find(
    (option) => option.value === progressFilter
  );

  const handleSelect = (item: DropdownItem): void => {
    setProgressFilter(item.value as ClassRankProgressFilter);
  };

  const handleClear = (): void => {
    setProgressFilter(ClassRankProgressFilter.ALL);
  };

  return (
    <div className="flex min-h-0 flex-col gap-2 lg:h-full">
      <div>
        <h3 className="text-sm font-semibold tracking-wide text-gray-900 uppercase dark:text-gray-100">
          Other Classes
        </h3>
        <p className="text-xs text-gray-600 dark:text-gray-400">
          Select a Class to view its progression, specialties, and requirements.
        </p>
      </div>

      <div>
        <label
          id="other-classes-progress-filter-label"
          className="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
        >
          Filter Class progress
        </label>
        <Dropdown
          aria_labelled_by="other-classes-progress-filter-label"
          items={progressFilterOptions}
          selection_placeholder="Filter Class progress"
          pre_selected_item={preSelectedItem}
          on_select={handleSelect}
          on_clear={handleClear}
        />
      </div>

      <div className="min-h-0 flex-1">
        <InfiniteScroll
          height_class="h-full min-h-0"
          additional_css="pr-1"
          handle_scroll={handleScroll}
        >
          <div className="flex flex-col gap-2">
            {visibleClasses.map((classRank) => (
              <OtherClassCard
                key={classRank.game_class_id}
                class_rank={classRank}
                on_click={onOpenClass}
              />
            ))}
          </div>
        </InfiniteScroll>
      </div>
    </div>
  );
};

export default OtherClassesPanel;
