import React, { Fragment, ReactNode } from 'react';

import { gemTypeLabel } from '../../shared/enums/gem-type';
import AdminRolledGemCard from '../../shared/gems/components/admin-rolled-gem-card';
import { MAP_GEM_RANGE_DISPLAY_GROUPS } from '../definitions/map-gem-range-display';
import MapGemDetailBodyProps from './types/map-gem-detail-body-props';

import {
  formatPercentRange,
  hasPositiveRangeValue,
} from 'game-utils/format-number';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MapGemDetailBody = ({
  map_gem: mapGem,
  on_activate_roll: onActivateRoll,
  activating_gem_id: activatingGemId,
}: MapGemDetailBodyProps): ReactNode => {
  const renderActiveRoll = (): ReactNode => {
    if (!mapGem.rolled_gem) {
      return (
        <Card>
          <p className="text-glacier-800 dark:text-glacier-200">
            No Gem has been rolled for this profile yet.
          </p>
        </Card>
      );
    }

    return (
      <AdminRolledGemCard
        roll={mapGem.rolled_gem}
        source_label="Game Map"
        source_name={mapGem.game_map.name}
        display_groups={MAP_GEM_RANGE_DISPLAY_GROUPS}
      />
    );
  };

  const renderRollHistory = (): ReactNode => {
    const previousRolls = mapGem.roll_history.filter((roll) => !roll.is_active);

    if (previousRolls.length === 0) {
      return null;
    }

    return (
      <div>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-lg font-semibold">
          Roll History
        </h2>
        <div className="space-y-3">
          {previousRolls.map((roll) => (
            <AdminRolledGemCard
              key={roll.id}
              roll={roll}
              source_label="Game Map"
              source_name={mapGem.game_map.name}
              display_groups={MAP_GEM_RANGE_DISPLAY_GROUPS}
              on_activate={
                onActivateRoll ? () => onActivateRoll(roll.id) : undefined
              }
              activating={activatingGemId === roll.id}
            />
          ))}
        </div>
      </div>
    );
  };

  const hasMeaningfulAtonementRange = hasPositiveRangeValue(
    mapGem.monster_atonement_range
  );

  return (
    <Fragment>
      <Card>
        <Dl>
          <Dt>Game Map</Dt>
          <Dd>{mapGem.game_map.name}</Dd>
          {mapGem.roll_count > 0 && (
            <Fragment>
              <Dt>Roll Count</Dt>
              <Dd>{mapGem.roll_count}</Dd>
            </Fragment>
          )}
        </Dl>
        {mapGem.description && (
          <p className="text-glacier-800 dark:text-glacier-200 mt-4">
            {mapGem.description}
          </p>
        )}
      </Card>

      <div>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-lg font-semibold">
          Active Roll
        </h2>
        {renderActiveRoll()}
      </div>

      {renderRollHistory()}

      <div>
        <h2 className="text-glacier-700 dark:text-glacier-300 mb-2 text-base font-semibold">
          Profile Configuration
        </h2>
        <div className="space-y-4">
          {MAP_GEM_RANGE_DISPLAY_GROUPS.map((group) => {
            const visibleFields = group.fields
              .map((field) => ({
                field,
                range: mapGem.ranges[field.range_field],
              }))
              .filter(
                (
                  entry
                ): entry is { field: typeof entry.field; range: string } =>
                  hasPositiveRangeValue(entry.range)
              );

            if (visibleFields.length === 0) {
              return null;
            }

            return (
              <Card key={group.title}>
                <h3 className="text-glacier-900 dark:text-glacier-100 mb-4 text-base font-semibold">
                  {group.title}
                </h3>
                <Dl>
                  {visibleFields.map(({ field, range }) => (
                    <Fragment key={field.range_field}>
                      <Dt>{field.label}</Dt>
                      <Dd>{formatPercentRange(range)}</Dd>
                    </Fragment>
                  ))}
                </Dl>
              </Card>
            );
          })}

          {mapGem.crafting_skills.length > 0 && (
            <Card>
              <h3 className="text-glacier-900 dark:text-glacier-100 mb-4 text-base font-semibold">
                Crafting Skills
              </h3>
              <p className="text-glacier-800 dark:text-glacier-200">
                {mapGem.crafting_skills.map((skill) => skill.name).join(', ')}
              </p>
            </Card>
          )}

          {(mapGem.monster_atonement !== null ||
            hasMeaningfulAtonementRange) && (
            <Card>
              <Dl>
                {mapGem.monster_atonement !== null && (
                  <Fragment>
                    <Dt>Monster Atonement</Dt>
                    <Dd>{gemTypeLabel(mapGem.monster_atonement)}</Dd>
                  </Fragment>
                )}
                {hasMeaningfulAtonementRange &&
                  mapGem.monster_atonement_range && (
                    <Fragment>
                      <Dt>Monster Atonement Range</Dt>
                      <Dd>
                        {formatPercentRange(mapGem.monster_atonement_range)}
                      </Dd>
                    </Fragment>
                  )}
              </Dl>
            </Card>
          )}
        </div>
      </div>

      {mapGem.generated_gem_world && (
        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Generated Gem World
          </h2>
          <Dl>
            <Dt>Gem World ID</Dt>
            <Dd>{mapGem.generated_gem_world.id}</Dd>
            <Dt>Name</Dt>
            <Dd>{mapGem.generated_gem_world.name}</Dd>
            {mapGem.generated_gem_world.generated_map_type && (
              <Fragment>
                <Dt>Generated Map Type</Dt>
                <Dd>{mapGem.generated_gem_world.generated_map_type}</Dd>
              </Fragment>
            )}
            {mapGem.generated_gem_world.parent_map && (
              <Fragment>
                <Dt>Parent Map ID</Dt>
                <Dd>{mapGem.generated_gem_world.parent_map.id}</Dd>
                <Dt>Parent Map Name</Dt>
                <Dd>{mapGem.generated_gem_world.parent_map.name}</Dd>
              </Fragment>
            )}
          </Dl>
        </Card>
      )}
    </Fragment>
  );
};

export default MapGemDetailBody;
