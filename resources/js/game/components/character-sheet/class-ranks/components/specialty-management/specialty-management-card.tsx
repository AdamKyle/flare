import React, { ReactNode } from 'react';

import SpecialtyStateBadges from './specialty-state-badges';
import SpecialtyManagementCardProps from './types/specialty-management-card-props';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const masteredStyles =
  'border-de-york-400 dark:border-de-york-500 bg-de-york-100 dark:bg-de-york-100 hover:bg-de-york-200 dark:hover:bg-de-york-200 text-de-york-900 dark:text-de-york-900';

const lockedStyles =
  'border-mango-tango-400 dark:border-mango-tango-500 bg-mango-tango-100 dark:bg-mango-tango-100 hover:bg-mango-tango-200 dark:hover:bg-mango-tango-200 text-mango-tango-900 dark:text-mango-tango-900';

const normalStyles =
  'border-glacier-400 dark:border-glacier-500 bg-glacier-100 dark:bg-glacier-100 hover:bg-glacier-200 dark:hover:bg-glacier-200 text-glacier-900 dark:text-glacier-900';

const resolveCardStyles = (
  row: SpecialtyManagementCardProps['row']
): string => {
  if (row.is_mastered) {
    return masteredStyles;
  }

  if (row.is_locked_level) {
    return lockedStyles;
  }

  return normalStyles;
};

const buildAccessibleLabel = (
  row: SpecialtyManagementCardProps['row']
): string => {
  const stateParts: string[] = [];

  if (row.is_equipped) {
    stateParts.push('Equipped');
  }

  if (row.is_mastered) {
    stateParts.push('Mastered');
  }

  if (row.is_in_progress) {
    stateParts.push('In Progress');
  }

  if (row.is_available) {
    stateParts.push('Available');
  }

  if (row.is_locked_level) {
    stateParts.push(
      `Requires Class Level ${row.definition.requires_class_rank_level}`
    );
  }

  const typeLabel = row.is_damage ? 'Damage' : 'Passive';

  return [`View ${row.definition.name}`, typeLabel, ...stateParts].join(', ');
};

const SpecialtyManagementCard = ({
  row,
  on_click: onClick,
}: SpecialtyManagementCardProps): ReactNode => {
  const currentClassLevel = row.owning_class_rank?.level ?? 0;

  return (
    <button
      type="button"
      onClick={() => onClick(row.definition.id)}
      aria-label={buildAccessibleLabel(row)}
      className={`${resolveCardStyles(row)} focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex w-full flex-col gap-2 rounded-lg border-2 p-3 text-left transition-colors focus:outline-none focus-visible:ring-2`}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="flex flex-col">
          <span className="font-semibold">{row.definition.name}</span>
          <span className="text-xs">{row.definition.class_name}</span>
        </div>
        <SpecialtyStateBadges
          is_damage={row.is_damage}
          is_equipped={row.is_equipped}
          is_mastered={row.is_mastered}
          is_in_progress={row.is_in_progress}
          is_available={row.is_available}
          is_locked_level={row.is_locked_level}
          required_class_level={row.definition.requires_class_rank_level}
        />
      </div>

      <span className="text-xs">
        Requires Class Level {row.definition.requires_class_rank_level}
        {' · '}Current Class Level {currentClassLevel}
      </span>

      {row.progress && (
        <ProgressBar
          value={row.progress.is_mastered ? 1 : row.progress.current_xp}
          max={row.progress.is_mastered ? 1 : row.progress.required_xp}
          size={ProgressBarSize.THIN}
          label={`Specialty Level ${row.progress.level}`}
          value_label={
            row.progress.is_mastered
              ? 'Mastered'
              : `${row.progress.current_xp} / ${row.progress.required_xp} XP`
          }
          variant={ProgressBarVariant.ARTIC}
        />
      )}
    </button>
  );
};

export default SpecialtyManagementCard;
