import React, { ReactNode } from 'react';

import { useClassRanksApi } from './class-ranks/api/hooks/use-class-ranks-api';
import { useClassSpecialtiesApi } from './class-ranks/api/hooks/use-class-specialties-api';
import CurrentClassOverview from './class-ranks/components/current-class-overview';
import OtherClassesPanel from './class-ranks/components/other-classes-panel';
import { useOpenCharacterClassRankDetail } from './class-ranks/hooks/use-open-character-class-rank-detail';
import { useOpenCharacterClassSpecialtyDetail } from './class-ranks/hooks/use-open-character-class-specialty-detail';
import { useOpenManageSpecialties } from './class-ranks/hooks/use-open-manage-specialties';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const CharacterClassRanks = (): ReactNode => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;

  const classRanksApi = useClassRanksApi({ characterId });
  const classSpecialtiesApi = useClassSpecialtiesApi({ characterId });

  const { openCharacterClassRankDetail } = useOpenCharacterClassRankDetail();
  const { openCharacterClassSpecialtyDetail } =
    useOpenCharacterClassSpecialtyDetail();
  const { openManageSpecialties } = useOpenManageSpecialties();

  if (classRanksApi.loading && classRanksApi.data.length === 0) {
    return <InfiniteLoader />;
  }

  if (classRanksApi.error) {
    return <Alert variant={AlertVariant.DANGER}>{classRanksApi.error}</Alert>;
  }

  const activeRank = classRanksApi.data.find(
    (classRank) => classRank.is_active
  );
  const otherClasses = classRanksApi.data.filter(
    (classRank) => !classRank.is_active
  );

  if (!activeRank) {
    return (
      <Alert variant={AlertVariant.DANGER}>
        Unable to determine your current Class.
      </Alert>
    );
  }

  const handleManageSpecialties = (): void => {
    openManageSpecialties(characterId);
  };

  const handleOpenSpecialty = (specialtyId: number): void => {
    const specialty = classSpecialtiesApi.data?.specials_equipped.find(
      (specialEquipped) => specialEquipped.game_class_special_id === specialtyId
    );

    openCharacterClassSpecialtyDetail(
      characterId,
      specialtyId,
      specialty?.class_mastery.name ?? 'Specialty'
    );
  };

  const handleOpenClass = (gameClassId: number): void => {
    const classRank = classRanksApi.data.find(
      (rank) => rank.game_class_id === gameClassId
    );

    if (!classRank) {
      return;
    }

    openCharacterClassRankDetail(
      characterId,
      gameClassId,
      classRank.class_name
    );
  };

  return (
    <div className="grid min-h-0 grid-cols-1 gap-4 p-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
      <div className="min-h-0">
        <CurrentClassOverview
          active_rank={activeRank}
          specialties_equipped={
            classSpecialtiesApi.data?.specials_equipped ?? []
          }
          specialties_loading={classSpecialtiesApi.loading}
          on_open_class={() => handleOpenClass(activeRank.game_class_id)}
          on_open_specialty={handleOpenSpecialty}
          on_manage_specialties={handleManageSpecialties}
        />
      </div>
      <div className="flex min-h-0 flex-col overflow-hidden lg:h-0 lg:min-h-full">
        <OtherClassesPanel
          other_classes={otherClasses}
          on_open_class={handleOpenClass}
        />
      </div>
    </div>
  );
};

export default CharacterClassRanks;
