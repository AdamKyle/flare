import React, { ReactNode } from 'react';

import EquippedSpecialtiesSummaryProps from './types/equipped-specialties-summary-props';
import ClassSpecialtySummaryCard from '../../../../reusable-components/class-mastery/components/class-specialty-summary-card';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const EquippedSpecialtiesSummary = ({
  specialties_equipped: specialtiesEquipped,
  specialties_loading: specialtiesLoading,
  on_open_specialty: onOpenSpecialty,
  on_manage_specialties: onManageSpecialties,
}: EquippedSpecialtiesSummaryProps): ReactNode => {
  const renderBody = (): ReactNode => {
    if (specialtiesLoading) {
      return <InfiniteLoader />;
    }

    if (specialtiesEquipped.length === 0) {
      return (
        <p className="text-glacier-700 dark:text-glacier-300 text-sm">
          No Class Specialties are equipped.
        </p>
      );
    }

    return (
      <div className="flex flex-col gap-2">
        {specialtiesEquipped.map((specialEquipped) => (
          <ClassSpecialtySummaryCard
            key={specialEquipped.id}
            class_mastery={specialEquipped.class_mastery}
            progress={{
              level: specialEquipped.level,
              current_xp: specialEquipped.current_xp,
              required_xp: specialEquipped.required_xp,
              specialty_damage: specialEquipped.specialty_damage,
            }}
            on_click={() =>
              onOpenSpecialty(specialEquipped.game_class_special_id)
            }
          />
        ))}
      </div>
    );
  };

  return (
    <div className="flex flex-col gap-2">
      <h3 className="text-glacier-900 dark:text-glacier-100 text-sm font-semibold tracking-wide uppercase">
        Equipped Specialties
      </h3>
      {renderBody()}
      <Button
        label="Manage Specialties"
        on_click={onManageSpecialties}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full mt-1"
      />
    </div>
  );
};

export default EquippedSpecialtiesSummary;
