import React, { ReactNode, useId } from 'react';

import FactionLoyaltyNpcCardProps from './types/faction-loyalty-npc-card-props';
import { factionCardStyles } from '../styles/faction-card-styles';
import { isFactionLoyaltyNpcMastered } from '../utils/is-faction-loyalty-npc-mastered';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const FactionLoyaltyNpcCard = ({
  faction_loyalty_npc: factionLoyaltyNpc,
  on_open: onOpen,
}: FactionLoyaltyNpcCardProps): ReactNode => {
  const fameLabelId = useId();

  const isMastered = isFactionLoyaltyNpcMastered(factionLoyaltyNpc);

  const renderProgress = (): ReactNode => {
    if (isMastered) {
      return (
        <p className="flex items-center gap-1 text-sm font-medium text-emerald-700 dark:text-emerald-400">
          <i className="fas fa-crown" aria-hidden="true" />
          Mastered
        </p>
      );
    }

    return (
      <ProgressBar
        label="Fame towards next level"
        aria_labelledby={fameLabelId}
        value={factionLoyaltyNpc.current_fame}
        max={factionLoyaltyNpc.next_level_fame}
        value_label={`${factionLoyaltyNpc.current_fame} / ${factionLoyaltyNpc.next_level_fame}`}
        variant={ProgressBarVariant.XP}
        size={ProgressBarSize.THIN}
      />
    );
  };

  const renderAssistingState = (): ReactNode => {
    if (!factionLoyaltyNpc.currently_helping) {
      return null;
    }

    return (
      <span className="text-danube-700 dark:text-danube-300 flex items-center gap-1 text-xs font-medium">
        <i className="fas fa-handshake" aria-hidden="true" />
        Assisting
      </span>
    );
  };

  return (
    <article className={factionCardStyles}>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h4 className="font-semibold">{factionLoyaltyNpc.npc.real_name}</h4>
        {renderAssistingState()}
      </div>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Level {factionLoyaltyNpc.current_level} / {factionLoyaltyNpc.max_level}
      </p>
      {renderProgress()}
      <Button
        label="View NPC Tasks"
        aria_label={`View ${factionLoyaltyNpc.npc.real_name}'s tasks`}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => onOpen(factionLoyaltyNpc.id)}
      />
    </article>
  );
};

export default FactionLoyaltyNpcCard;
