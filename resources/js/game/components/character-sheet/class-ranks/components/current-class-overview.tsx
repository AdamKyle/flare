import React, { ReactNode } from 'react';

import CompactWeaponMastery from './compact-weapon-mastery';
import EquippedSpecialtiesSummary from './equipped-specialties-summary';
import { useProgressiveClassRankList } from '../hooks/use-progressive-class-rank-list';
import CurrentClassOverviewProps from './types/current-class-overview-props';

import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CurrentClassOverview = ({
  active_rank: activeRank,
  specialties_equipped: specialtiesEquipped,
  specialties_loading: specialtiesLoading,
  on_open_class: onOpenClass,
  on_open_specialty: onOpenSpecialty,
  on_manage_specialties: onManageSpecialties,
}: CurrentClassOverviewProps): ReactNode => {
  const { visible_count: visibleCount, handle_scroll: handleScroll } =
    useProgressiveClassRankList({
      total_items: activeRank.weapon_masteries.length,
      reset_key: activeRank.game_class_id,
    });

  const visibleWeaponMasteries = activeRank.weapon_masteries.slice(
    0,
    visibleCount
  );

  const renderMasteredBadge = (): ReactNode => {
    if (!activeRank.is_mastered) {
      return null;
    }

    return (
      <span className="bg-de-york-100 text-de-york-800 dark:bg-de-york-900/40 dark:text-de-york-200 inline-flex self-start rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
        Mastered
      </span>
    );
  };

  const renderLevelProgress = (): ReactNode => {
    if (activeRank.is_mastered) {
      return (
        <ProgressBar
          value={1}
          max={1}
          size={ProgressBarSize.THIN}
          label={`Level: ${activeRank.level}`}
          value_label="Mastered"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    return (
      <ProgressBar
        value={activeRank.current_xp}
        max={activeRank.required_xp}
        size={ProgressBarSize.THIN}
        label={`Level: ${activeRank.level}`}
        value_label={`${activeRank.current_xp} / ${activeRank.required_xp} XP`}
        variant={ProgressBarVariant.PRIMARY}
      />
    );
  };

  const renderWeaponMasteries = (): ReactNode => {
    if (activeRank.weapon_masteries.length === 0) {
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
          handle_scroll={handleScroll}
        >
          <div className="flex flex-col gap-2">
            {visibleWeaponMasteries.map((weaponMastery) => (
              <CompactWeaponMastery
                key={weaponMastery.id}
                weapon_mastery={weaponMastery}
                progress_variant={ProgressBarVariant.PRIMARY}
              />
            ))}
          </div>
        </InfiniteScroll>
      </div>
    );
  };

  return (
    <div className="border-danube-300 bg-danube-50/70 dark:border-danube-700 dark:bg-danube-950/20 flex flex-col gap-4 rounded-lg border-l-2 p-4">
      <div>
        <span className="text-glacier-600 dark:text-glacier-400 text-xs font-semibold tracking-wide uppercase">
          Current Class
        </span>
        <button
          type="button"
          onClick={onOpenClass}
          aria-label={`View ${activeRank.class_name} Class details`}
          className="text-danube-800 hover:text-danube-600 focus-visible:ring-danube-500 dark:text-danube-200 dark:hover:text-danube-100 block w-full rounded-sm text-left text-2xl font-bold transition-colors focus:outline-none focus-visible:ring-2"
        >
          {activeRank.class_name}
        </button>
      </div>

      {activeRank.class_detail.description && (
        <p className="text-glacier-800 dark:text-glacier-200 text-sm">
          {activeRank.class_detail.description}
        </p>
      )}

      <div className="flex flex-col gap-2">
        {renderMasteredBadge()}
        {renderLevelProgress()}
      </div>

      <EquippedSpecialtiesSummary
        specialties_equipped={specialtiesEquipped}
        specialties_loading={specialtiesLoading}
        on_open_specialty={onOpenSpecialty}
        on_manage_specialties={onManageSpecialties}
      />

      {renderWeaponMasteries()}
    </div>
  );
};

export default CurrentClassOverview;
