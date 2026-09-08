import React, { ReactNode, useState } from 'react';

import CharacterClassRankDetailSidePeekProps from './types/character-class-rank-detail-side-peek-props';
import { useClassRanksApi } from '../../character-sheet/class-ranks/api/hooks/use-class-ranks-api';
import { useClassSpecialtiesApi } from '../../character-sheet/class-ranks/api/hooks/use-class-specialties-api';
import ClassRankDetailContent from '../../character-sheet/class-ranks/components/class-rank-detail-content';
import ClassRankDetailStack from '../../character-sheet/class-ranks/components/class-rank-detail-stack';
import { useOpenCharacterClassSpecialtyDetail } from '../../character-sheet/class-ranks/hooks/use-open-character-class-specialty-detail';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const CharacterClassRankDetailSidePeek = ({
  character_id: characterId,
  game_class_id: gameClassId,
}: CharacterClassRankDetailSidePeekProps): ReactNode => {
  const { gameData } = useGameData();

  const automationRestricted =
    gameData?.character?.is_automation_running === true ||
    gameData?.character?.is_faction_loyalty_automation_running === true;

  const classRanksApi = useClassRanksApi({ characterId });
  const classSpecialtiesApi = useClassSpecialtiesApi({ characterId });
  const { openCharacterClassSpecialtyDetail } =
    useOpenCharacterClassSpecialtyDetail();

  const [nestedGameClassId, setNestedGameClassId] = useState<number | null>(
    null
  );

  if (classRanksApi.loading || classSpecialtiesApi.loading) {
    return (
      <div className="p-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (classRanksApi.error || !classSpecialtiesApi.data) {
    return (
      <div className="p-4">
        <Alert variant={AlertVariant.DANGER}>
          {classRanksApi.error ??
            classSpecialtiesApi.error ??
            'Unable to load Class Rank details.'}
        </Alert>
      </div>
    );
  }

  const selectedRank = classRanksApi.data.find(
    (classRank) => classRank.game_class_id === gameClassId
  );

  if (!selectedRank) {
    return (
      <div className="p-4">
        <Alert variant={AlertVariant.DANGER}>
          That Class could not be found.
        </Alert>
      </div>
    );
  }

  const handleOpenSpecialty = (gameClassSpecialId: number): void => {
    const specialty = classSpecialtiesApi.data?.class_specialties.find(
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
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
        <ClassRankDetailContent
          selected_rank={selectedRank}
          rank_catalog={classRanksApi.data}
          class_specialties={classSpecialtiesApi.data.class_specialties}
          automation_restricted={automationRestricted}
          switching_class_id={classRanksApi.switchingClassId}
          switch_error={classRanksApi.mutationError}
          on_switch_class={(id) => void classRanksApi.switchClass(id)}
          on_open_class={setNestedGameClassId}
          on_open_specialty={handleOpenSpecialty}
        />
      </div>

      {nestedGameClassId !== null && (
        <ClassRankDetailStack
          character_id={characterId}
          game_class_id={nestedGameClassId}
          rank_catalog={classRanksApi.data}
          class_specialties={classSpecialtiesApi.data.class_specialties}
          automation_restricted={automationRestricted}
          switching_class_id={classRanksApi.switchingClassId}
          switch_error={classRanksApi.mutationError}
          on_close={() => setNestedGameClassId(null)}
          on_switch_class={(id) => void classRanksApi.switchClass(id)}
        />
      )}
    </div>
  );
};

export default CharacterClassRankDetailSidePeek;
