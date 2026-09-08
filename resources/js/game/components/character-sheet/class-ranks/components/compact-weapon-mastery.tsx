import React, { ReactNode } from 'react';

import CompactWeaponMasteryProps from './types/compact-weapon-mastery-props';

import { ProgressBarSize } from 'ui/progress/enums/progress-bar-size';
import ProgressBar from 'ui/progress/progress-bar';

const formatWeaponType = (weaponType: string): string =>
  weaponType
    .split('-')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');

const CompactWeaponMastery = ({
  weapon_mastery: weaponMastery,
  progress_variant: progressVariant,
}: CompactWeaponMasteryProps): ReactNode => {
  const renderProgress = (): ReactNode => {
    if (weaponMastery.is_mastered) {
      return (
        <ProgressBar
          value={1}
          max={1}
          size={ProgressBarSize.THIN}
          label={`Level ${weaponMastery.level}`}
          value_label="Mastered"
          variant={progressVariant}
        />
      );
    }

    return (
      <ProgressBar
        value={weaponMastery.current_xp}
        max={weaponMastery.required_xp}
        size={ProgressBarSize.THIN}
        label={`Level ${weaponMastery.level}`}
        value_label={`${weaponMastery.current_xp} / ${weaponMastery.required_xp} XP`}
        variant={progressVariant}
      />
    );
  };

  return (
    <div className="bg-glacier-50/60 dark:bg-glacier-950/20 rounded-md px-3 py-2">
      <div className="mb-1 flex flex-wrap items-baseline justify-between gap-1">
        <span className="text-glacier-900 dark:text-glacier-100 text-sm font-semibold">
          {weaponMastery.mastery_name}
        </span>
        <span className="text-glacier-600 dark:text-glacier-400 text-xs">
          {formatWeaponType(weaponMastery.weapon_type)}
        </span>
      </div>
      {renderProgress()}
    </div>
  );
};

export default CompactWeaponMastery;
