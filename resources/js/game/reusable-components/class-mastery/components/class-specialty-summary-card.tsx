import React, { ReactNode } from 'react';

import ClassSpecialtySummaryCardProps from '../types/class-specialty-summary-card-props';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const baseStyles =
  'w-full flex flex-col gap-1 rounded-lg border-2 p-3 text-left transition-colors border-glacier-300 dark:border-glacier-700 bg-glacier-50 dark:bg-glacier-950/40';

const interactiveStyles =
  'hover:bg-glacier-100 dark:hover:bg-glacier-900/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 cursor-pointer';

const ClassSpecialtySummaryCard = ({
  class_mastery: classMastery,
  progress,
  progress_variant: progressVariant = ProgressBarVariant.ARTIC,
  on_click: onClick,
  status_text: statusText,
}: ClassSpecialtySummaryCardProps): ReactNode => {
  const renderContent = (): ReactNode => (
    <>
      <div className="flex items-start justify-between gap-2">
        <span className="text-glacier-900 dark:text-glacier-100 font-semibold">
          {classMastery.name}
        </span>
        {statusText && (
          <span className="bg-danube-100 text-danube-800 dark:bg-danube-900/60 dark:text-danube-200 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
            {statusText}
          </span>
        )}
      </div>
      <span className="text-glacier-600 dark:text-glacier-400 text-xs">
        {classMastery.type === 'attack' ? 'Attack' : 'Passive'} · Requires Class
        Rank Level {classMastery.requires_class_rank_level}
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
            <span className="text-glacier-700 dark:text-glacier-300 text-xs">
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
        className={`${baseStyles} ${interactiveStyles}`}
      >
        {renderContent()}
      </button>
    );
  }

  return (
    <div aria-label={classMastery.name} className={baseStyles}>
      {renderContent()}
    </div>
  );
};

export default ClassSpecialtySummaryCard;
