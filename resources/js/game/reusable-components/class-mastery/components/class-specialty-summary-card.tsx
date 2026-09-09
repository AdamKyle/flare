import clsx from 'clsx';
import React, { ReactNode } from 'react';

import ClassSpecialtySummaryCardProps from '../types/class-specialty-summary-card-props';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import {
  cardMutedTextVariantStyles,
  cardSurfaceVariantStyles,
  cardTextVariantStyles,
} from 'ui/progress/styles/progress-bar/card-variant-styles';

const ClassSpecialtySummaryCard = ({
  class_mastery: classMastery,
  progress,
  progress_variant: progressVariant = ProgressBarVariant.ARTIC,
  on_click: onClick,
  status_text: statusText,
}: ClassSpecialtySummaryCardProps): ReactNode => {
  const surfaceStyles = cardSurfaceVariantStyles(progressVariant);
  const textStyles = cardTextVariantStyles(progressVariant);
  const mutedTextStyles = cardMutedTextVariantStyles(progressVariant);

  const renderContent = (): ReactNode => (
    <>
      <div className="flex items-start justify-between gap-2">
        <span className={clsx('font-semibold', textStyles)}>
          {classMastery.name}
        </span>
        {statusText && (
          <span className="bg-danube-200 text-danube-900 dark:bg-danube-200 dark:text-danube-900 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
            {statusText}
          </span>
        )}
      </div>
      <span className={clsx('text-xs', mutedTextStyles)}>
        {classMastery.type === 'attack' ? 'Attack' : 'Passive'} · Requires Class
        Level {classMastery.requires_class_rank_level}
      </span>
      {progress && (
        <div className="mt-1 flex flex-col gap-1">
          <ProgressBar
            value={progress.current_xp}
            max={progress.required_xp}
            size={ProgressBarSize.THIN}
            label={`Specialty Level ${progress.level}`}
            value_label={`${progress.current_xp} / ${progress.required_xp} XP`}
            variant={progressVariant}
          />
          {progress.specialty_damage > 0 && (
            <span className="text-wisp-pink-800 dark:text-wisp-pink-800 text-xs">
              Specialty Damage: {progress.specialty_damage}
            </span>
          )}
        </div>
      )}
    </>
  );

  if (onClick) {
    return (
      <button
        type="button"
        onClick={() => onClick(classMastery.id)}
        aria-label={`View ${classMastery.name} Specialty`}
        className={clsx(
          'w-full rounded-lg border-2 p-3 text-left transition-colors',
          surfaceStyles,
          textStyles,
          'focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 cursor-pointer focus:outline-none focus-visible:ring-2'
        )}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <div
      aria-label={classMastery.name}
      className={clsx(
        'w-full rounded-lg border-2 p-3 text-left transition-colors',
        surfaceStyles,
        textStyles
      )}
    >
      {renderContent()}
    </div>
  );
};

export default ClassSpecialtySummaryCard;
