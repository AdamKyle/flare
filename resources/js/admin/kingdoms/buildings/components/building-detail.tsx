import React, { ReactNode } from 'react';

import BuildingDetailProps from './types/building-detail-props';
import PositiveDetailRow from '../../../../game/reusable-components/kingdom/components/positive-detail-row';
import UnitCard from '../../../../game/reusable-components/kingdom/units/components/unit-card';
import BuildingRecruitableUnitDefinition from '../api/definitions/building-recruitable-unit-definition';

import Card from 'ui/cards/card';
import Dl from 'ui/dl/dl';
import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import Separator from 'ui/separator/separator';

const BuildingDetail = ({
  building,
  on_open_unit: onOpenUnit,
}: BuildingDetailProps): ReactNode => {
  const progressiveUnits = useProgressiveList({
    total_items: building.units.length,
    initial_count: 5,
    batch_size: 5,
    reset_key: building.id,
  });

  const renderFacts = (): ReactNode => {
    const facts = [
      building.is_walls && 'Wall',
      building.is_farm && 'Farm',
      building.is_church && 'Church',
      building.is_resource_building && 'Resource Building',
      building.is_special && 'Special',
      building.is_locked && 'Locked',
      building.trains_units && 'Trains Units',
    ].filter((fact): fact is string => Boolean(fact));

    if (facts.length === 0) {
      return null;
    }

    return (
      <div className="mb-4 flex flex-wrap gap-2">
        {facts.map((fact) => (
          <span
            key={fact}
            className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300"
          >
            {fact}
          </span>
        ))}
      </div>
    );
  };

  const renderRecruitableUnit = (
    unit: BuildingRecruitableUnitDefinition
  ): ReactNode => (
    <li key={unit.unit_id}>
      <UnitCard
        unit_id={unit.unit_id}
        name={unit.unit_name ?? `Unit ${unit.unit_id}`}
        required_level={unit.required_level}
        on_open_unit={onOpenUnit}
      />
    </li>
  );

  const renderRecruitableUnits = (): ReactNode => {
    if (building.units.length === 0) {
      return (
        <p className="text-gray-700 dark:text-gray-300">
          This Building does not recruit any Units.
        </p>
      );
    }

    const visibleUnits = building.units.slice(
      0,
      progressiveUnits.visible_count
    );

    return (
      <>
        <InfiniteScroll
          handle_scroll={progressiveUnits.handle_scroll}
          additional_css="max-h-64"
        >
          <ul className="space-y-2">
            {visibleUnits.map(renderRecruitableUnit)}
          </ul>
        </InfiniteScroll>
        <p
          className="mt-2 text-sm text-gray-600 dark:text-gray-400"
          role="status"
        >
          Showing {visibleUnits.length} of {building.units.length} Units.
        </p>
      </>
    );
  };

  return (
    <Card>
      {building.description && (
        <p className="mb-4 text-gray-800 dark:text-gray-200">
          {building.description}
        </p>
      )}
      {renderFacts()}

      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Basic
      </h3>
      <Dl>
        <PositiveDetailRow label="Max Level" value={building.max_level} />
        <PositiveDetailRow
          label="Base Required Population"
          value={building.required_population}
        />
        <PositiveDetailRow
          label="Base Durability"
          value={building.base_durability}
        />
        <PositiveDetailRow label="Base Defence" value={building.base_defence} />
      </Dl>

      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Costs
      </h3>
      <Dl>
        <PositiveDetailRow label="Wood" value={building.wood_cost} />
        <PositiveDetailRow label="Clay" value={building.clay_cost} />
        <PositiveDetailRow label="Stone" value={building.stone_cost} />
        <PositiveDetailRow label="Iron" value={building.iron_cost} />
        <PositiveDetailRow label="Steel" value={building.steel_cost} />
        <PositiveDetailRow
          label="Time to Build (minutes)"
          value={building.time_to_build}
        />
        <PositiveDetailRow
          label="Time Increase Per Level %"
          value={building.time_increase_amount}
        />
      </Dl>

      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Upgrade Effects
      </h3>
      <Dl>
        <PositiveDetailRow
          label="Increase Population Amount"
          value={building.increase_population_amount}
        />
        <PositiveDetailRow
          label="Increase Morale %"
          value={building.increase_morale_amount}
        />
        <PositiveDetailRow
          label="Decrease Morale %"
          value={building.decrease_morale_amount}
        />
        <PositiveDetailRow
          label="Increase Wood"
          value={building.increase_wood_amount}
        />
        <PositiveDetailRow
          label="Increase Clay"
          value={building.increase_clay_amount}
        />
        <PositiveDetailRow
          label="Increase Stone"
          value={building.increase_stone_amount}
        />
        <PositiveDetailRow
          label="Increase Iron"
          value={building.increase_iron_amount}
        />
        <PositiveDetailRow
          label="Increase Durability"
          value={building.increase_durability_amount}
        />
        <PositiveDetailRow
          label="Increase Defence"
          value={building.increase_defence_amount}
        />
      </Dl>

      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Unit Recruitment
      </h3>
      <Dl>
        <PositiveDetailRow
          label="Units Per Level"
          value={building.units_per_level}
        />
        <PositiveDetailRow
          label="Unit At Only Level"
          value={building.only_at_level}
        />
      </Dl>
      <h4 className="mt-4 mb-2 font-semibold text-gray-900 dark:text-gray-100">
        Recruitable Units
      </h4>
      {renderRecruitableUnits()}

      {(building.passive_skill || (building.level_required ?? 0) > 0) && (
        <Separator />
      )}
      {building.passive_skill && (
        <p className="text-sm text-gray-800 dark:text-gray-200">
          Passive Skill Required: {building.passive_skill.name}
        </p>
      )}
      <Dl>
        <PositiveDetailRow
          label="Passive Level Required"
          value={building.level_required}
        />
      </Dl>
    </Card>
  );
};

export default BuildingDetail;
