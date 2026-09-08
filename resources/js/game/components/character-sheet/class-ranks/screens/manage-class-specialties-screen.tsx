import React, { ReactNode, useEffect, useMemo, useRef } from 'react';

import ManageClassSpecialtiesScreenProps from './types/manage-class-specialties-screen-props';
import { useClassRanksApi } from '../api/hooks/use-class-ranks-api';
import { useClassSpecialtiesApi } from '../api/hooks/use-class-specialties-api';
import EquippedSpecialtiesPanel from '../components/specialty-management/equipped-specialties-panel';
import SpecialtyLibrary from '../components/specialty-management/specialty-library';
import { buildSpecialtyManagementRows } from '../components/specialty-management/utils/build-specialty-management-rows';
import { useOpenCharacterClassSpecialtyDetail } from '../hooks/use-open-character-class-specialty-detail';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const ManageClassSpecialtiesScreen = ({
  character_id: characterId,
  initial_specialty_id: initialSpecialtyId,
  on_close: onClose,
}: ManageClassSpecialtiesScreenProps): ReactNode => {
  const classRanksApi = useClassRanksApi({ characterId });
  const classSpecialtiesApi = useClassSpecialtiesApi({ characterId });
  const { openCharacterClassSpecialtyDetail } =
    useOpenCharacterClassSpecialtyDetail();

  const hasOpenedInitialSpecialtyRef = useRef(false);

  const rows = useMemo(
    () =>
      classSpecialtiesApi.data
        ? buildSpecialtyManagementRows(
            classSpecialtiesApi.data.class_specialties,
            classSpecialtiesApi.data.specials_equipped,
            classSpecialtiesApi.data.other_class_specials,
            classRanksApi.data
          )
        : [],
    [classSpecialtiesApi.data, classRanksApi.data]
  );

  useEffect(() => {
    if (hasOpenedInitialSpecialtyRef.current || initialSpecialtyId === null) {
      return;
    }

    const initialRow = rows.find(
      (row) => row.definition.id === initialSpecialtyId
    );

    if (!initialRow) {
      return;
    }

    hasOpenedInitialSpecialtyRef.current = true;
    openCharacterClassSpecialtyDetail(
      characterId,
      initialSpecialtyId,
      initialRow.definition.name
    );
  }, [
    rows,
    initialSpecialtyId,
    characterId,
    openCharacterClassSpecialtyDetail,
  ]);

  if (classRanksApi.loading || classSpecialtiesApi.loading) {
    return (
      <ContainerWithTitle
        title="Manage Class Specialties"
        manageSectionVisibility={onClose}
      >
        <Card>
          <InfiniteLoader />
        </Card>
      </ContainerWithTitle>
    );
  }

  if (classRanksApi.error || !classSpecialtiesApi.data) {
    return (
      <ContainerWithTitle
        title="Manage Class Specialties"
        manageSectionVisibility={onClose}
      >
        <Card>
          <Alert variant={AlertVariant.DANGER}>
            {classRanksApi.error ??
              classSpecialtiesApi.error ??
              'Unable to load Class Specialties.'}
          </Alert>
        </Card>
      </ContainerWithTitle>
    );
  }

  const currentGameClassId =
    classRanksApi.data.find((classRank) => classRank.is_active)
      ?.game_class_id ?? 0;

  const equippedRows = rows.filter((row) => row.is_equipped);

  const handleOpenSpecialty = (gameClassSpecialId: number): void => {
    const row = rows.find(
      (candidate) => candidate.definition.id === gameClassSpecialId
    );

    if (!row) {
      return;
    }

    openCharacterClassSpecialtyDetail(
      characterId,
      gameClassSpecialId,
      row.definition.name
    );
  };

  return (
    <ContainerWithTitle
      title="Manage Class Specialties"
      manageSectionVisibility={onClose}
    >
      <div className="flex flex-col">
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          Equip up to 3 Class Specialties. At most 1 equipped Specialty may be a
          Damage Specialty.
        </p>

        <Separator additional_css="my-4" />

        <EquippedSpecialtiesPanel
          equipped_rows={equippedRows}
          on_open_specialty={handleOpenSpecialty}
        />

        <Separator additional_css="my-4" />

        <SpecialtyLibrary
          rows={rows}
          class_ranks={classRanksApi.data}
          current_game_class_id={currentGameClassId}
          on_open_specialty={handleOpenSpecialty}
        />
      </div>
    </ContainerWithTitle>
  );
};

export default ManageClassSpecialtiesScreen;
