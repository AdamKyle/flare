import React, { ReactNode, useMemo, useState } from 'react';

import { SpecialtyProgressFilter } from './enums/specialty-progress-filter';
import SpecialtyManagementCard from './specialty-management-card';
import SpecialtyLibraryProps from './types/specialty-library-props';
import SpecialtyManagementRowDefinition from './types/specialty-management-row-definition';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Separator from 'ui/separator/separator';

const statusOptions: DropdownItem[] = [
  { label: 'Mastered', value: SpecialtyProgressFilter.MASTERED },
  { label: 'In Progress', value: SpecialtyProgressFilter.IN_PROGRESS },
  { label: 'Available', value: SpecialtyProgressFilter.AVAILABLE },
];

const resolveRowSortPriority = (
  row: SpecialtyManagementRowDefinition
): number => {
  if (row.is_equipped) {
    return 0;
  }

  if (row.is_mastered) {
    return 1;
  }

  if (row.is_in_progress) {
    return 2;
  }

  if (row.is_available) {
    return 3;
  }

  return 4;
};

const matchesStatusFilter = (
  row: SpecialtyManagementRowDefinition,
  filter: SpecialtyProgressFilter
): boolean => {
  switch (filter) {
    case SpecialtyProgressFilter.MASTERED:
      return row.is_mastered;
    case SpecialtyProgressFilter.IN_PROGRESS:
      return row.is_in_progress;
    case SpecialtyProgressFilter.AVAILABLE:
      return row.is_available;
    default:
      return true;
  }
};

const SpecialtyLibrary = ({
  rows,
  class_ranks: classRanks,
  current_game_class_id: currentGameClassId,
  on_open_specialty: onOpenSpecialty,
}: SpecialtyLibraryProps): ReactNode => {
  const [selectedClassId, setSelectedClassId] =
    useState<number>(currentGameClassId);
  const [statusFilter, setStatusFilter] = useState<SpecialtyProgressFilter>(
    SpecialtyProgressFilter.ALL
  );

  const classOptions = useMemo((): DropdownItem[] => {
    const currentRank = classRanks.find(
      (classRank) => classRank.game_class_id === currentGameClassId
    );

    const otherUnlockedRanks = classRanks
      .filter(
        (classRank) =>
          classRank.game_class_id !== currentGameClassId && !classRank.is_locked
      )
      .sort((a, b) => a.class_name.localeCompare(b.class_name));

    const orderedRanks = currentRank
      ? [currentRank, ...otherUnlockedRanks]
      : otherUnlockedRanks;

    return orderedRanks.map((classRank) => ({
      label: classRank.class_name,
      value: classRank.game_class_id,
    }));
  }, [classRanks, currentGameClassId]);

  const preSelectedClassOption = classOptions.find(
    (option) => option.value === selectedClassId
  );

  const preSelectedStatusOption = statusOptions.find(
    (option) => option.value === statusFilter
  );

  const visibleRows = rows
    .filter((row) => !row.is_equipped)
    .filter((row) => row.definition.game_class_id === selectedClassId)
    .filter((row) => matchesStatusFilter(row, statusFilter))
    .sort((a, b) => {
      const priorityComparison =
        resolveRowSortPriority(a) - resolveRowSortPriority(b);

      if (priorityComparison !== 0) {
        return priorityComparison;
      }

      return a.definition.name.localeCompare(b.definition.name);
    });

  return (
    <section
      aria-labelledby="specialty-library-heading"
      className="flex flex-col gap-3"
    >
      <h3
        id="specialty-library-heading"
        className="text-sm font-semibold tracking-wide text-gray-900 uppercase dark:text-gray-100"
      >
        Available Specialties
      </h3>

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="flex-1">
          <label
            id="specialty-library-class-label"
            className="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
          >
            Class
          </label>
          <Dropdown
            aria_labelled_by="specialty-library-class-label"
            items={classOptions}
            selection_placeholder="Select a Class"
            pre_selected_item={preSelectedClassOption}
            on_select={(item) => setSelectedClassId(Number(item.value))}
          />
        </div>
        <div className="flex-1">
          <label
            id="specialty-library-status-label"
            className="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
          >
            Status
          </label>
          <Dropdown
            aria_labelled_by="specialty-library-status-label"
            items={statusOptions}
            selection_placeholder="All"
            pre_selected_item={preSelectedStatusOption}
            on_select={(item) =>
              setStatusFilter(item.value as SpecialtyProgressFilter)
            }
            on_clear={() => setStatusFilter(SpecialtyProgressFilter.ALL)}
          />
        </div>
      </div>

      <Separator additional_css="my-3" />

      {visibleRows.length === 0 ? (
        <p className="text-sm text-gray-700 dark:text-gray-300">
          No Class Specialties match this filter.
        </p>
      ) : (
        <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
          {visibleRows.map((row) => (
            <SpecialtyManagementCard
              key={row.definition.id}
              row={row}
              on_click={onOpenSpecialty}
            />
          ))}
        </div>
      )}
    </section>
  );
};

export default SpecialtyLibrary;
