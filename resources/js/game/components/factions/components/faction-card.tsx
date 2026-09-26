import React, { ReactNode, useId } from 'react';

import FactionCardProps from './types/faction-card-props';
import { factionCardStyles } from '../styles/faction-card-styles';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const FactionCard = ({
  faction,
  is_pledged: isPledged,
  on_open: onOpen,
}: FactionCardProps): ReactNode => {
  const progressLabelId = useId();

  const renderMasteredState = (): ReactNode => {
    if (!faction.maxed) {
      return null;
    }

    return (
      <span className="flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-400">
        <i className="fas fa-crown" aria-hidden="true" />
        Mastered
      </span>
    );
  };

  const renderPledgedState = (): ReactNode => {
    if (!isPledged) {
      return null;
    }

    return (
      <span className="text-danube-700 dark:text-danube-300 flex items-center gap-1 text-xs font-medium">
        <i className="fas fa-handshake" aria-hidden="true" />
        Pledged
      </span>
    );
  };

  const renderTitle = (): ReactNode => {
    if (!faction.title) {
      return null;
    }

    return <span> · {faction.title}</span>;
  };

  const renderProgress = (): ReactNode => {
    if (faction.maxed) {
      return null;
    }

    return (
      <ProgressBar
        label="Faction points"
        aria_labelledby={progressLabelId}
        value={faction.current_points}
        max={faction.points_needed}
        value_label={`${faction.current_points} / ${faction.points_needed}`}
        variant={ProgressBarVariant.XP}
        size={ProgressBarSize.THIN}
      />
    );
  };

  return (
    <article className={factionCardStyles}>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3 className="font-semibold">{faction.map_name}</h3>
        <div className="flex flex-wrap items-center gap-3">
          {renderMasteredState()}
          {renderPledgedState()}
        </div>
      </div>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Level {faction.current_level}
        {renderTitle()}
      </p>
      {renderProgress()}
      <Button
        label="View Faction"
        aria_label={`View the ${faction.map_name} Faction`}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => onOpen(faction.id)}
      />
    </article>
  );
};

export default FactionCard;
