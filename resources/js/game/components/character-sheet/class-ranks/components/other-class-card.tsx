import React, { ReactNode } from 'react';

import { ClassRankVisualState } from '../enums/class-rank-visual-state';
import OtherClassCardProps from './types/other-class-card-props';
import { resolveClassRankVisualState } from '../utils/resolve-class-rank-visual-state';

const lockedStyles =
  'border-mango-tango-400 dark:border-mango-tango-500 bg-mango-tango-100 dark:bg-mango-tango-100 hover:bg-mango-tango-200 dark:hover:bg-mango-tango-200 text-mango-tango-900 dark:text-mango-tango-900';

const masteredStyles =
  'border-de-york-400 dark:border-de-york-500 bg-de-york-100 dark:bg-de-york-100 hover:bg-de-york-200 dark:hover:bg-de-york-200 text-de-york-900 dark:text-de-york-900';

const unlockedStyles =
  'border-glacier-400 dark:border-glacier-500 bg-glacier-100 dark:bg-glacier-100 hover:bg-glacier-200 dark:hover:bg-glacier-200 text-glacier-900 dark:text-glacier-900';

const resolveCardStyles = (visualState: ClassRankVisualState): string => {
  if (visualState === ClassRankVisualState.LOCKED) {
    return lockedStyles;
  }

  if (visualState === ClassRankVisualState.MASTERED) {
    return masteredStyles;
  }

  return unlockedStyles;
};

const OtherClassCard = ({
  class_rank: classRank,
  on_click: onClick,
}: OtherClassCardProps): ReactNode => {
  const visualState = resolveClassRankVisualState(classRank);
  const isLocked = visualState === ClassRankVisualState.LOCKED;
  const isMastered = visualState === ClassRankVisualState.MASTERED;

  const renderRequirementSummary = (): ReactNode => {
    if (!isLocked || !classRank.unlock_progress) {
      return null;
    }

    const { primary, secondary } = classRank.unlock_progress;

    return (
      <div className="text-mango-tango-900 dark:text-mango-tango-900 flex flex-col text-xs">
        <span>
          {primary.name}: {primary.current_level} / {primary.required_level}
        </span>
        <span>
          {secondary.name}: {secondary.current_level} /{' '}
          {secondary.required_level}
        </span>
      </div>
    );
  };

  const renderStateSummary = (): ReactNode => {
    if (isLocked) {
      return (
        <span className="mt-1 flex items-center gap-1 text-xs font-medium">
          <i className="fas fa-lock" aria-hidden="true" />
          Locked
        </span>
      );
    }

    if (isMastered) {
      return (
        <span className="mt-1 text-xs font-medium">
          Level: {classRank.level} · Mastered
        </span>
      );
    }

    return (
      <span className="mt-1 text-xs font-medium">
        Level: {classRank.level} · {classRank.current_xp} /{' '}
        {classRank.required_xp} XP
      </span>
    );
  };

  return (
    <button
      type="button"
      onClick={() => onClick(classRank.game_class_id)}
      aria-label={`View ${classRank.class_name} Class`}
      className={`focus:outline-none focus-visible:ring-2 ${resolveCardStyles(
        visualState
      )} w-full rounded-lg border-2 p-3 text-left transition-colors`}
    >
      <div className="flex items-center justify-between gap-2">
        <div className="flex min-w-0 flex-col gap-0.5">
          <span className="truncate font-semibold">{classRank.class_name}</span>
          {renderStateSummary()}
          {renderRequirementSummary()}
        </div>
      </div>
    </button>
  );
};

export default OtherClassCard;
