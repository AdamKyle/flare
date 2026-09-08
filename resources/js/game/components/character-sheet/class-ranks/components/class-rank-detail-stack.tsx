import React, { ReactNode, useState } from 'react';

import ClassRankDetailContent from './class-rank-detail-content';
import ClassRankDetailStackProps from './types/class-rank-detail-stack-props';
import { useOpenCharacterClassSpecialtyDetail } from '../hooks/use-open-character-class-specialty-detail';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const ClassRankDetailStack = (props: ClassRankDetailStackProps): ReactNode => {
  const {
    character_id: characterId,
    game_class_id: gameClassId,
    rank_catalog: rankCatalog,
    class_specialties: classSpecialties,
    automation_restricted: automationRestricted,
    switching_class_id: switchingClassId,
    switch_error: switchError,
    on_close: onClose,
    on_switch_class: onSwitchClass,
  } = props;

  const [nestedGameClassId, setNestedGameClassId] = useState<number | null>(
    null
  );

  const { openCharacterClassSpecialtyDetail } =
    useOpenCharacterClassSpecialtyDetail();

  const selectedRank = rankCatalog.find(
    (classRank) => classRank.game_class_id === gameClassId
  );

  if (!selectedRank) {
    return null;
  }

  const handleOpenSpecialty = (gameClassSpecialId: number): void => {
    const specialty = classSpecialties.find(
      (candidate) => candidate.id === gameClassSpecialId
    );

    if (!specialty) {
      return;
    }

    openCharacterClassSpecialtyDetail(
      characterId,
      gameClassSpecialId,
      specialty.name
    );
  };

  return (
    <StackedCard
      on_close={onClose}
      aria_label={`${selectedRank.class_name} Class Details`}
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          <ClassRankDetailContent
            selected_rank={selectedRank}
            rank_catalog={rankCatalog}
            class_specialties={classSpecialties}
            automation_restricted={automationRestricted}
            switching_class_id={switchingClassId}
            switch_error={switchError}
            on_switch_class={onSwitchClass}
            on_open_class={setNestedGameClassId}
            on_open_specialty={handleOpenSpecialty}
          />
        </div>

        {nestedGameClassId !== null && (
          <ClassRankDetailStack
            {...props}
            game_class_id={nestedGameClassId}
            on_close={() => setNestedGameClassId(null)}
          />
        )}
      </div>
    </StackedCard>
  );
};

export default ClassRankDetailStack;
