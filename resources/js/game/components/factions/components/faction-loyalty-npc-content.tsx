import React, { ReactNode, useId } from 'react';

import FactionLoyaltyAutomationSetup from './faction-loyalty-automation-setup';
import FactionLoyaltyTaskList from './faction-loyalty-task-list';
import FactionLoyaltyNpcContentProps from './types/faction-loyalty-npc-content-props';
import { isFactionLoyaltyNpcMastered } from '../utils/is-faction-loyalty-npc-mastered';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const FactionLoyaltyNpcContent = ({
  character_id: characterId,
  faction_loyalty_npc: factionLoyaltyNpc,
  submitting,
  error,
  assistance_message: assistanceMessage,
  on_assist: onAssist,
  on_stop_assisting: onStopAssisting,
  on_view_npc_details: onViewNpcDetails,
}: FactionLoyaltyNpcContentProps): ReactNode => {
  const fameLabelId = useId();

  const isMastered = isFactionLoyaltyNpcMastered(factionLoyaltyNpc);
  const kingdomItemDefenceBonus = (
    factionLoyaltyNpc.current_kingdom_item_defence_bonus * 100
  ).toFixed(2);

  const renderFeedback = (): ReactNode => {
    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (assistanceMessage !== null) {
      return <Alert variant={AlertVariant.SUCCESS}>{assistanceMessage}</Alert>;
    }

    return null;
  };

  const renderAssistanceAction = (): ReactNode => {
    if (factionLoyaltyNpc.currently_helping) {
      return (
        <Button
          label="Stop Assisting"
          variant={ButtonVariant.DANGER}
          additional_css="w-full"
          disabled={submitting}
          on_click={onStopAssisting}
        />
      );
    }

    return (
      <Button
        label="Assist This NPC"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={submitting}
        on_click={onAssist}
      />
    );
  };

  const renderAutomationSetup = (): ReactNode => {
    if (!factionLoyaltyNpc.currently_helping) {
      return null;
    }

    return (
      <FactionLoyaltyAutomationSetup
        character_id={characterId}
        faction_loyalty_npc={factionLoyaltyNpc}
      />
    );
  };

  const renderMasteredContent = (): ReactNode => (
    <Alert variant={AlertVariant.SUCCESS}>
      You have completed all of this NPC&apos;s tasks. Because you are pledged
      to this Faction, your kingdoms on the plane this NPC lives on receive an
      Item Defence bonus based on this NPC&apos;s level and how many NPCs you
      have helped. This bonus applies to all present and future kingdoms.
    </Alert>
  );

  const renderProgressContent = (): ReactNode => (
    <>
      <ProgressBar
        label="Fame towards next level"
        aria_labelledby={fameLabelId}
        value={factionLoyaltyNpc.current_fame}
        max={factionLoyaltyNpc.next_level_fame}
        value_label={`${factionLoyaltyNpc.current_fame} / ${factionLoyaltyNpc.next_level_fame}`}
        variant={ProgressBarVariant.XP}
      />
      {renderFeedback()}
      {renderAssistanceAction()}
      <section aria-label="Tasks" className="flex flex-col gap-2">
        <h3 className="font-semibold text-gray-900 dark:text-gray-100">
          Tasks
        </h3>
        <FactionLoyaltyTaskList
          tasks={factionLoyaltyNpc.faction_loyalty_npc_tasks?.fame_tasks ?? []}
        />
      </section>
      {renderAutomationSetup()}
    </>
  );

  const renderNpcProgress = (): ReactNode => {
    if (isMastered) {
      return renderMasteredContent();
    }

    return renderProgressContent();
  };

  return (
    <div className="flex flex-col gap-4">
      <Dl>
        <Dt>Level</Dt>
        <Dd>
          {factionLoyaltyNpc.current_level} / {factionLoyaltyNpc.max_level}
        </Dd>
        <Dt>Assisting</Dt>
        <Dd>{factionLoyaltyNpc.currently_helping ? 'Yes' : 'No'}</Dd>
        <Dt>Kingdom Item Defence Bonus</Dt>
        <Dd>{kingdomItemDefenceBonus}%</Dd>
      </Dl>
      <Button
        label="View NPC Details"
        aria_label={`View ${factionLoyaltyNpc.npc.real_name} details`}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={onViewNpcDetails}
      />
      {renderNpcProgress()}
    </div>
  );
};

export default FactionLoyaltyNpcContent;
