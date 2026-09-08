import React, { Fragment, ReactNode, useState } from 'react';

import CharacterClassSpecialtyDetailSidePeekProps from './types/character-class-specialty-detail-side-peek-props';
import ClassMasteryDetail from '../../../reusable-components/class-mastery/components/class-mastery-detail';
import { useClassRanksApi } from '../../character-sheet/class-ranks/api/hooks/use-class-ranks-api';
import { useClassSpecialtiesApi } from '../../character-sheet/class-ranks/api/hooks/use-class-specialties-api';
import { buildSpecialtyManagementRows } from '../../character-sheet/class-ranks/components/specialty-management/utils/build-specialty-management-rows';
import SpecialtyReplacementPicker from '../../character-sheet/class-ranks/components/specialty-replacement-picker';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const CharacterClassSpecialtyDetailSidePeek = ({
  character_id: characterId,
  game_class_special_id: gameClassSpecialId,
}: CharacterClassSpecialtyDetailSidePeekProps): ReactNode => {
  const classRanksApi = useClassRanksApi({ characterId });
  const classSpecialtiesApi = useClassSpecialtiesApi({ characterId });

  const [selectedReplacementId, setSelectedReplacementId] = useState<
    number | null
  >(null);

  const rows = classSpecialtiesApi.data
    ? buildSpecialtyManagementRows(
        classSpecialtiesApi.data.class_specialties,
        classSpecialtiesApi.data.specials_equipped,
        classSpecialtiesApi.data.other_class_specials,
        classRanksApi.data
      )
    : [];

  const targetRow = rows.find(
    (row) => row.definition.id === gameClassSpecialId
  );

  const specialsEquipped = classSpecialtiesApi.data?.specials_equipped ?? [];

  const equippedRow = targetRow?.is_equipped
    ? (specialsEquipped.find(
        (specialEquipped) =>
          specialEquipped.game_class_special_id === gameClassSpecialId
      ) ?? null)
    : null;

  const isDamageSpecialty = targetRow?.is_damage ?? false;

  const equippedDamageSpecialty =
    specialsEquipped.find(
      (specialEquipped) => specialEquipped.specialty_damage > 0
    ) ?? null;

  const isBusy =
    classSpecialtiesApi.equippingSpecialtyId === gameClassSpecialId ||
    classSpecialtiesApi.swappingSpecialtyId === gameClassSpecialId ||
    (!!equippedRow &&
      classSpecialtiesApi.unequippingSpecialtyId === equippedRow.id);

  const resolveFooterOptions = (): SidePeekOptionDefinition[] => {
    if (!targetRow) {
      return [];
    }

    if (equippedRow) {
      return [
        {
          id: 'unequip',
          label: 'Unequip',
          loading_label: 'Unequipping...',
          variant: ButtonVariant.DANGER,
          loading: isBusy,
          on_click: () =>
            void classSpecialtiesApi.unequipSpecialty(equippedRow.id),
        },
      ];
    }

    if (!targetRow.is_accessible) {
      return [];
    }

    if (
      specialsEquipped.length < 3 &&
      !(isDamageSpecialty && equippedDamageSpecialty)
    ) {
      return [
        {
          id: 'equip',
          label: 'Equip',
          loading_label: 'Equipping...',
          variant: ButtonVariant.PRIMARY,
          loading: isBusy,
          on_click: () =>
            void classSpecialtiesApi.equipSpecialty(gameClassSpecialId),
        },
      ];
    }

    if (isDamageSpecialty && equippedDamageSpecialty) {
      return [
        {
          id: 'replace-damage',
          label: `Replace ${equippedDamageSpecialty.class_mastery.name}`,
          loading_label: 'Replacing...',
          variant: ButtonVariant.PRIMARY,
          loading: isBusy,
          on_click: () =>
            void classSpecialtiesApi.swapSpecialty(
              gameClassSpecialId,
              equippedDamageSpecialty.id
            ),
        },
      ];
    }

    return [
      {
        id: 'replace-specialty',
        label: 'Replace Specialty',
        loading_label: 'Replacing...',
        variant: ButtonVariant.PRIMARY,
        disabled: selectedReplacementId === null,
        loading: isBusy,
        on_click: () =>
          selectedReplacementId !== null &&
          void classSpecialtiesApi.swapSpecialty(
            gameClassSpecialId,
            selectedReplacementId
          ),
      },
    ];
  };

  useSidePeekOptions(resolveFooterOptions());

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
            'Unable to load Class Specialty details.'}
        </Alert>
      </div>
    );
  }

  if (!targetRow) {
    return (
      <div className="p-4">
        <Alert variant={AlertVariant.DANGER}>
          That Class Specialty could not be found.
        </Alert>
      </div>
    );
  }

  const targetSpecialty = targetRow.definition;
  const ownerRank = classRanksApi.data.find(
    (classRank) => classRank.game_class_id === targetSpecialty.game_class_id
  );
  const learnedProgress = equippedRow ?? targetRow.progress ?? null;

  const renderLearnedProgress = (): ReactNode => {
    if (!learnedProgress) {
      return null;
    }

    return (
      <ProgressBar
        value={learnedProgress.is_mastered ? 1 : learnedProgress.current_xp}
        max={learnedProgress.is_mastered ? 1 : learnedProgress.required_xp}
        size={ProgressBarSize.THIN}
        label={`Specialty Level ${learnedProgress.level}`}
        value_label={
          learnedProgress.is_mastered
            ? 'Mastered'
            : `${learnedProgress.current_xp} / ${learnedProgress.required_xp} XP`
        }
        variant={ProgressBarVariant.ARTIC}
      />
    );
  };

  const currentClassLevel = ownerRank?.level ?? 0;

  const renderTopContext = (): ReactNode => {
    if (equippedRow) {
      return (
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          This Specialty is currently equipped.
        </p>
      );
    }

    if (targetRow.is_locked_level) {
      return (
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          Requires Class Level {targetSpecialty.requires_class_rank_level}.
          Current Class Level {currentClassLevel}.
        </p>
      );
    }

    if (!targetRow.is_accessible) {
      return null;
    }

    if (
      specialsEquipped.length < 3 &&
      !(isDamageSpecialty && equippedDamageSpecialty)
    ) {
      return (
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          This Specialty can be equipped.
        </p>
      );
    }

    if (isDamageSpecialty && equippedDamageSpecialty) {
      return (
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          Only one Damage Specialty may be equipped. Equipping{' '}
          {targetSpecialty.name} will replace{' '}
          {equippedDamageSpecialty.class_mastery.name}.
        </p>
      );
    }

    return (
      <div className="flex flex-col gap-3">
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          All 3 Specialty slots are full. Choose a Specialty to replace.
        </p>
        <SpecialtyReplacementPicker
          equipped_specialties={specialsEquipped}
          selected_id={selectedReplacementId}
          on_select={setSelectedReplacementId}
        />
      </div>
    );
  };

  const topContext = renderTopContext();

  return (
    <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
      {topContext}
      {topContext && <Separator additional_css="my-0" />}
      <ClassMasteryDetail class_mastery={targetSpecialty.class_mastery} />
      <Separator additional_css="my-0" />
      <Dl>
        <Dt>Class</Dt>
        <Dd>{targetSpecialty.class_name}</Dd>
        <Dt>Requires Class Level</Dt>
        <Dd>{targetSpecialty.requires_class_rank_level}</Dd>
        {ownerRank && (
          <Fragment>
            <Dt>Current Class Level</Dt>
            <Dd>{ownerRank.level}</Dd>
          </Fragment>
        )}
      </Dl>
      {renderLearnedProgress()}
      {classSpecialtiesApi.mutationError && (
        <Alert variant={AlertVariant.DANGER}>
          {classSpecialtiesApi.mutationError}
        </Alert>
      )}
    </div>
  );
};

export default CharacterClassSpecialtyDetailSidePeek;
