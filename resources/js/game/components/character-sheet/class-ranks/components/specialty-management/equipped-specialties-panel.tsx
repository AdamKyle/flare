import React, { ReactNode } from 'react';

import SpecialtyManagementCard from './specialty-management-card';
import EquippedSpecialtiesPanelProps from './types/equipped-specialties-panel-props';

const MAX_EQUIPPED_SLOTS = 3;
const MAX_DAMAGE_SLOTS = 1;

const EquippedSpecialtiesPanel = ({
  equipped_rows: equippedRows,
  on_open_specialty: onOpenSpecialty,
}: EquippedSpecialtiesPanelProps): ReactNode => {
  const damageCount = equippedRows.filter((row) => row.is_damage).length;

  const slots = Array.from({ length: MAX_EQUIPPED_SLOTS }, (_, index) =>
    equippedRows[index] ? equippedRows[index] : null
  );

  return (
    <section
      aria-labelledby="equipped-specialties-heading"
      className="flex flex-col gap-3"
    >
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h3
          id="equipped-specialties-heading"
          className="text-sm font-semibold tracking-wide text-gray-900 uppercase dark:text-gray-100"
        >
          Equipped Specialties
        </h3>
        <div className="flex gap-3 text-xs font-medium text-gray-700 dark:text-gray-300">
          <span>
            {equippedRows.length} / {MAX_EQUIPPED_SLOTS} equipped
          </span>
          <span>
            Damage: {damageCount} / {MAX_DAMAGE_SLOTS}
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
        {slots.map((row, index) =>
          row ? (
            <SpecialtyManagementCard
              key={row.definition.id}
              row={row}
              on_click={onOpenSpecialty}
            />
          ) : (
            <div
              key={`empty-slot-${index}`}
              className="border-glacier-300 dark:border-glacier-700 flex min-h-[6rem] w-full items-center justify-center rounded-lg border-2 border-dashed p-3 text-center text-sm text-gray-500 dark:text-gray-500"
            >
              Empty Specialty Slot
            </div>
          )
        )}
      </div>
    </section>
  );
};

export default EquippedSpecialtiesPanel;
