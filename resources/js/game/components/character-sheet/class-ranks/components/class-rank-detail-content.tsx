import React, { ReactNode } from 'react';

import CompactWeaponMastery from './compact-weapon-mastery';
import { ClassRankVisualState } from '../enums/class-rank-visual-state';
import { useProgressiveClassRankList } from '../hooks/use-progressive-class-rank-list';
import { resolveClassRankProgressVariant } from '../utils/resolve-class-rank-progress-variant';
import { resolveClassRankVisualState } from '../utils/resolve-class-rank-visual-state';
import ClassRankDetailContentProps from './types/class-rank-detail-content-props';
import ClassDetail from '../../../../reusable-components/class/components/class-detail';
import ClassSpecialtySummaryCard from '../../../../reusable-components/class-mastery/components/class-specialty-summary-card';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';

const stateSurfaceStyles: Record<ClassRankVisualState, string> = {
  [ClassRankVisualState.CURRENT]:
    'border-danube-300 bg-danube-50 dark:border-danube-500 dark:bg-danube-100',
  [ClassRankVisualState.MASTERED]:
    'border-de-york-300 bg-de-york-50 dark:border-de-york-500 dark:bg-de-york-100',
  [ClassRankVisualState.UNLOCKED]:
    'border-glacier-300 bg-glacier-50 dark:border-glacier-500 dark:bg-glacier-100',
  [ClassRankVisualState.LOCKED]:
    'border-mango-tango-300 bg-mango-tango-50 dark:border-mango-tango-500 dark:bg-mango-tango-100',
};

const ClassRankDetailContent = ({
  selected_rank: selectedRank,
  class_specialties: classSpecialties,
  automation_restricted: automationRestricted,
  switching_class_id: switchingClassId,
  switch_error: switchError,
  on_switch_class: onSwitchClass,
  on_open_class: onOpenClass,
  on_open_specialty: onOpenSpecialty,
  switch_class_action_in_footer: switchClassActionInFooter = false,
}: ClassRankDetailContentProps): ReactNode => {
  const visualState = resolveClassRankVisualState(selectedRank);
  const progressVariant = resolveClassRankProgressVariant(visualState);

  const belongingSpecialties = classSpecialties.filter(
    (specialty) => specialty.game_class_id === selectedRank.game_class_id
  );

  const isSwitching = switchingClassId === selectedRank.game_class_id;

  const weaponMasteries = useProgressiveClassRankList({
    total_items: selectedRank.weapon_masteries.length,
    reset_key: selectedRank.game_class_id,
  });

  const visibleWeaponMasteries = selectedRank.weapon_masteries.slice(
    0,
    weaponMasteries.visible_count
  );

  const specialties = useProgressiveClassRankList({
    total_items: belongingSpecialties.length,
    reset_key: selectedRank.game_class_id,
  });

  const visibleSpecialties = belongingSpecialties.slice(
    0,
    specialties.visible_count
  );

  const renderLevelProgress = (): ReactNode => {
    if (selectedRank.is_mastered) {
      return (
        <ProgressBar
          value={1}
          max={1}
          size={ProgressBarSize.THIN}
          label={`Level: ${selectedRank.level}`}
          value_label="Mastered"
          variant={progressVariant}
        />
      );
    }

    return (
      <ProgressBar
        value={selectedRank.current_xp}
        max={selectedRank.required_xp}
        size={ProgressBarSize.THIN}
        label={`Level: ${selectedRank.level}`}
        value_label={`${selectedRank.current_xp} / ${selectedRank.required_xp} XP`}
        variant={progressVariant}
      />
    );
  };

  const renderWeaponMasteries = (): ReactNode => {
    if (selectedRank.weapon_masteries.length === 0) {
      return null;
    }

    return (
      <div className="flex flex-col gap-2">
        <h3 className="text-sm font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200">
          Weapon Masteries
        </h3>
        <InfiniteScroll
          height_class="max-h-80"
          additional_css="pr-1"
          handle_scroll={weaponMasteries.handle_scroll}
        >
          <div className="flex flex-col gap-2">
            {visibleWeaponMasteries.map((weaponMastery) => (
              <CompactWeaponMastery
                key={weaponMastery.id}
                weapon_mastery={weaponMastery}
                progress_variant={progressVariant}
              />
            ))}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  const renderSpecialties = (): ReactNode => {
    if (belongingSpecialties.length === 0) {
      return null;
    }

    return (
      <div className="flex flex-col gap-2">
        <h3 className="text-sm font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200">
          Class Specialties
        </h3>
        <InfiniteScroll
          height_class="max-h-80"
          additional_css="pr-1"
          handle_scroll={specialties.handle_scroll}
        >
          <div className="flex flex-col gap-2">
            {visibleSpecialties.map((specialty) => (
              <ClassSpecialtySummaryCard
                key={specialty.id}
                class_mastery={specialty.class_mastery}
                progress_variant={progressVariant}
                on_click={onOpenSpecialty}
              />
            ))}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  const renderSwitchClass = (): ReactNode => {
    if (selectedRank.is_active) {
      return null;
    }

    if (switchClassActionInFooter) {
      return switchError ? (
        <Alert variant={AlertVariant.DANGER}>{switchError}</Alert>
      ) : null;
    }

    return (
      <div className="flex flex-col gap-2">
        {switchError && (
          <Alert variant={AlertVariant.DANGER}>{switchError}</Alert>
        )}
        <Button
          label="Switch Class"
          variant={ButtonVariant.PRIMARY}
          disabled={
            selectedRank.is_locked || automationRestricted || isSwitching
          }
          aria_busy={isSwitching}
          on_click={() => onSwitchClass(selectedRank.game_class_id)}
        />
      </div>
    );
  };

  const hasWeaponMasteries = selectedRank.weapon_masteries.length > 0;
  const hasSpecialties = belongingSpecialties.length > 0;
  const hasSwitchClass = switchClassActionInFooter
    ? !selectedRank.is_active && Boolean(switchError)
    : !selectedRank.is_active;

  return (
    <div className="flex flex-col">
      <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
        {selectedRank.class_name}
      </h2>

      <ClassDetail
        game_class={selectedRank.class_detail}
        on_open_class={onOpenClass}
        single_column
      />

      <Separator additional_css="my-0" />

      <div
        className={`flex flex-col gap-2 rounded-lg border p-3 ${stateSurfaceStyles[visualState]}`}
      >
        {renderLevelProgress()}
      </div>

      {hasWeaponMasteries && <Separator additional_css="my-0" />}
      {renderWeaponMasteries()}

      {hasSpecialties && <Separator additional_css="my-0" />}
      {renderSpecialties()}

      {hasSwitchClass && <Separator additional_css="my-0" />}
      {renderSwitchClass()}
    </div>
  );
};

export default ClassRankDetailContent;
