import React, { Fragment, ReactNode } from 'react';

import { gemTypeLabel } from '../../shared/enums/gem-type';
import AdminRolledGemCard from '../../shared/gems/components/admin-rolled-gem-card';
import { LOCATION_GEM_RANGE_DISPLAY_GROUPS } from '../definitions/location-gem-range-display';
import LocationGemDetailBodyProps from './types/location-gem-detail-body-props';

import {
  formatPercentRange,
  hasPositiveRangeValue,
} from 'game-utils/format-number';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Reusable factual/detail body for a Location Gem profile, shared by the
 * Admin Show screen and the Admin Location Gem detail SidePeek. Presents
 * identity, the currently active Gem roll, roll history, profile
 * configuration ranges, and the generated Gem World, in that order of
 * importance.
 */
const LocationGemDetailBody = ({
  location_gem: locationGem,
  on_activate_roll: onActivateRoll,
  activating_gem_id: activatingGemId,
}: LocationGemDetailBodyProps): ReactNode => {
  const renderActiveRoll = (): ReactNode => {
    if (!locationGem.rolled_gem) {
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
        roll={locationGem.rolled_gem}
        source_label="Location"
        source_name={locationGem.location.name}
        display_groups={LOCATION_GEM_RANGE_DISPLAY_GROUPS}
      />
    );
  };

  const renderRollHistory = (): ReactNode => {
    const previousRolls = locationGem.roll_history.filter(
      (roll) => !roll.is_active
    );

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
              source_label="Location"
              source_name={locationGem.location.name}
              display_groups={LOCATION_GEM_RANGE_DISPLAY_GROUPS}
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
    locationGem.monster_atonement_range
  );

  return (
    <Fragment>
      <Card>
        <Dl>
          <Dt>Game Map</Dt>
          <Dd>{locationGem.game_map.name}</Dd>
          <Dt>Location</Dt>
          <Dd>{locationGem.location.name}</Dd>
          {locationGem.roll_count > 0 && (
            <Fragment>
              <Dt>Roll Count</Dt>
              <Dd>{locationGem.roll_count}</Dd>
            </Fragment>
          )}
        </Dl>
        {locationGem.description && (
          <p className="text-glacier-800 dark:text-glacier-200 mt-4">
            {locationGem.description}
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
          {LOCATION_GEM_RANGE_DISPLAY_GROUPS.map((group) => {
            const visibleFields = group.fields
              .map((field) => ({
                field,
                range: locationGem.ranges[field.range_field],
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

          {locationGem.crafting_skills.length > 0 && (
            <Card>
              <h3 className="text-glacier-900 dark:text-glacier-100 mb-4 text-base font-semibold">
                Crafting Skills
              </h3>
              <p className="text-glacier-800 dark:text-glacier-200">
                {locationGem.crafting_skills
                  .map((skill) => skill.name)
                  .join(', ')}
              </p>
            </Card>
          )}

          {(locationGem.monster_atonement !== null ||
            hasMeaningfulAtonementRange) && (
            <Card>
              <Dl>
                {locationGem.monster_atonement !== null && (
                  <Fragment>
                    <Dt>Monster Atonement</Dt>
                    <Dd>{gemTypeLabel(locationGem.monster_atonement)}</Dd>
                  </Fragment>
                )}
                {hasMeaningfulAtonementRange &&
                  locationGem.monster_atonement_range && (
                    <Fragment>
                      <Dt>Monster Atonement Range</Dt>
                      <Dd>
                        {formatPercentRange(
                          locationGem.monster_atonement_range
                        )}
                      </Dd>
                    </Fragment>
                  )}
              </Dl>
            </Card>
          )}
        </div>
      </div>

      {locationGem.generated_gem_world && (
        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Generated Gem World
          </h2>
          <Dl>
            <Dt>Gem World ID</Dt>
            <Dd>{locationGem.generated_gem_world.id}</Dd>
            <Dt>Name</Dt>
            <Dd>{locationGem.generated_gem_world.name}</Dd>
            {locationGem.generated_gem_world.generated_map_type && (
              <Fragment>
                <Dt>Generated Map Type</Dt>
                <Dd>{locationGem.generated_gem_world.generated_map_type}</Dd>
              </Fragment>
            )}
            {locationGem.generated_gem_world.parent_map && (
              <Fragment>
                <Dt>Parent Map ID</Dt>
                <Dd>{locationGem.generated_gem_world.parent_map.id}</Dd>
                <Dt>Parent Map Name</Dt>
                <Dd>{locationGem.generated_gem_world.parent_map.name}</Dd>
              </Fragment>
            )}
          </Dl>
        </Card>
      )}
    </Fragment>
  );
};

export default LocationGemDetailBody;
