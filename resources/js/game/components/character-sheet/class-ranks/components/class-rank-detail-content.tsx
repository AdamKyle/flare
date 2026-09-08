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
    'border-danube-200 bg-danube-50/70 dark:border-danube-800/70 dark:bg-danube-950/20',
  [ClassRankVisualState.MASTERED]:
    'border-de-york-200 bg-de-york-50/70 dark:border-de-york-800/70 dark:bg-de-york-950/30',
  [ClassRankVisualState.UNLOCKED]:
    'border-glacier-200 bg-glacier-50/70 dark:border-glacier-800/70 dark:bg-glacier-950/30',
  [ClassRankVisualState.LOCKED]:
    'border-mango-tango-200 bg-mango-tango-50/70 dark:border-mango-tango-800/70 dark:bg-mango-tango-950/30',
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
        <h3 className="text-glacier-900 dark:text-glacier-100 text-sm font-semibold tracking-wide uppercase">
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
        <h3 className="text-glacier-900 dark:text-glacier-100 text-sm font-semibold tracking-wide uppercase">
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
  const hasSwitchClass = !selectedRank.is_active;

  return (
    <div className="flex flex-col gap-4">
      <h2 className="text-glacier-900 dark:text-glacier-100 text-xl font-bold">
        {selectedRank.class_name}
      </h2>

      <ClassDetail
        game_class={selectedRank.class_detail}
        on_open_class={onOpenClass}
      />

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
