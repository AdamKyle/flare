import React, { ReactNode } from 'react';

import UnitDetailProps from './types/unit-detail-props';
import BuildingCard from '../../../../game/reusable-components/kingdom/buildings/components/building-card';
import PositiveDetailRow from '../../../../game/reusable-components/kingdom/components/positive-detail-row';
import UnitRecruitingBuildingDefinition from '../api/definitions/unit-recruiting-building-definition';

import Card from 'ui/cards/card';
import Dl from 'ui/dl/dl';
import { useProgressiveList } from 'ui/infinite-scroll/hooks/use-progressive-list';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import Separator from 'ui/separator/separator';

const UnitDetail = ({
  unit,
  on_open_building: onOpenBuilding,
}: UnitDetailProps): ReactNode => {
  const progressiveBuildings = useProgressiveList({
    total_items: unit.recruited_from.length,
    initial_count: 5,
    batch_size: 5,
    reset_key: unit.id,
  });

  const facts = [
    unit.attacker && 'Attacker',
    unit.defender && 'Defender',
    unit.can_heal && 'Can Heal',
    unit.can_not_be_healed && 'Cannot Be Healed',
    unit.siege_weapon && 'Siege',
    unit.is_airship && 'Airship',
    unit.is_settler && 'Settler',
    unit.is_special && 'Special',
  ].filter((fact): fact is string => Boolean(fact));

  const renderRecruitingBuilding = (
    building: UnitRecruitingBuildingDefinition
  ): ReactNode => (
    <li key={building.building_id}>
      <BuildingCard
        building_id={building.building_id}
        name={building.building_name ?? `Building ${building.building_id}`}
        required_level={building.required_level}
        on_open_building={onOpenBuilding}
      />
    </li>
  );

  const renderBuildings = (): ReactNode => {
    if (unit.recruited_from.length === 0) {
      return (
        <p className="text-gray-700 dark:text-gray-300">
          No Building recruits this Unit.
        </p>
      );
    }

    const visibleBuildings = unit.recruited_from.slice(
      0,
      progressiveBuildings.visible_count
    );

    return (
      <>
        <InfiniteScroll
          handle_scroll={progressiveBuildings.handle_scroll}
          additional_css="max-h-64"
        >
          <ul className="space-y-2">
            {visibleBuildings.map(renderRecruitingBuilding)}
          </ul>
        </InfiniteScroll>
        <p
          className="mt-2 text-sm text-gray-600 dark:text-gray-400"
          role="status"
        >
          Showing {visibleBuildings.length} of {unit.recruited_from.length}{' '}
          Buildings.
        </p>
      </>
    );
  };

  return (
    <Card>
      {unit.description && (
        <p className="mb-4 text-gray-800 dark:text-gray-200">
          {unit.description}
        </p>
      )}
      {facts.length > 0 && (
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
      )}

      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Combat
      </h3>
      <Dl>
        <PositiveDetailRow label="Attack" value={unit.attack} />
        <PositiveDetailRow label="Defence" value={unit.defence} />
      </Dl>
      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Healing and Morale
      </h3>
      <Dl>
        <PositiveDetailRow label="Heal %" value={unit.heal_percentage} />
        <PositiveDetailRow
          label="Reduces Morale By %"
          value={unit.reduces_morale_by}
        />
      </Dl>
      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Costs
      </h3>
      <Dl>
        <PositiveDetailRow label="Wood" value={unit.wood_cost} />
        <PositiveDetailRow label="Clay" value={unit.clay_cost} />
        <PositiveDetailRow label="Stone" value={unit.stone_cost} />
        <PositiveDetailRow label="Iron" value={unit.iron_cost} />
        <PositiveDetailRow label="Steel" value={unit.steel_cost} />
        <PositiveDetailRow
          label="Population"
          value={unit.required_population}
        />
        <PositiveDetailRow
          label="Time to Recruit (seconds)"
          value={unit.time_to_recruit}
        />
      </Dl>
      <Separator />
      <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Recruited From
      </h3>
      {renderBuildings()}
    </Card>
  );
};

export default UnitDetail;
