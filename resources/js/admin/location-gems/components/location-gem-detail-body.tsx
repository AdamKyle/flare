import React, { Fragment, ReactNode } from 'react';

import { gemTypeLabel } from '../../shared/enums/gem-type';
import { LOCATION_GEM_RANGE_DISPLAY_GROUPS } from '../definitions/location-gem-range-display';
import LocationGemDetailBodyProps from './types/location-gem-detail-body-props';
import FactualLink from '../../../game/reusable-components/quest-item/partials/factual-link';

import {
  formatPercentRange,
  hasPositiveRangeValue,
} from 'game-utils/format-number';

import Card from 'ui/cards/card';
import DetailGrid from 'ui/detail-grid/detail-grid';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const sectionHeadingCss =
  'text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold';

const LocationGemDetailBody = ({
  location_gem: locationGem,
  is_side_peek: isSidePeek = false,
  navigation,
}: LocationGemDetailBodyProps): ReactNode => {
  const hasMeaningfulAtonementRange = hasPositiveRangeValue(
    locationGem.monster_atonement_range
  );

  const rangeGroups = LOCATION_GEM_RANGE_DISPLAY_GROUPS.map((group) => ({
    title: group.title,
    fields: group.fields
      .map((field) => ({
        field,
        range: locationGem.ranges[field.range_field],
      }))
      .filter((entry): entry is { field: typeof entry.field; range: string } =>
        hasPositiveRangeValue(entry.range)
      ),
  })).filter((group) => group.fields.length > 0);

  const hasCraftingSkills = locationGem.crafting_skills.length > 0;
  const hasAtonement =
    locationGem.monster_atonement !== null || hasMeaningfulAtonementRange;
  const hasGeneratedGemWorld = Boolean(locationGem.generated_gem_world);

  const identitySection = (
    <div>
      <Dl>
        <Dt>Game Map</Dt>
        <Dd>
          <FactualLink
            id={locationGem.game_map.id}
            label={locationGem.game_map.name}
            on_click={navigation?.on_open_map}
          />
        </Dd>
        <Dt>Location</Dt>
        <Dd>
          <FactualLink
            id={locationGem.location.id}
            label={locationGem.location.name}
            on_click={navigation?.on_open_location}
          />
        </Dd>
        {locationGem.roll_count > 0 && (
          <Fragment>
            <Dt>Roll Count</Dt>
            <Dd>{locationGem.roll_count}</Dd>
          </Fragment>
        )}
        {locationGem.rolled_gem && (
          <Fragment>
            <Dt>Active Roll</Dt>
            <Dd>Roll #{locationGem.rolled_gem.roll_number}</Dd>
          </Fragment>
        )}
      </Dl>
      {locationGem.description && (
        <p className="mt-2 text-gray-700 dark:text-gray-300">
          {locationGem.description}
        </p>
      )}
    </div>
  );

  const rangeGroupSections = rangeGroups.map((group) => (
    <div key={group.title}>
      <h3 className={sectionHeadingCss}>{group.title}</h3>
      <Dl>
        {group.fields.map(({ field, range }) => (
          <Fragment key={field.range_field}>
            <Dt>{field.label}</Dt>
            <Dd>{formatPercentRange(range)}</Dd>
          </Fragment>
        ))}
      </Dl>
    </div>
  ));

  const craftingSkillsSection = hasCraftingSkills ? (
    <div>
      <h3 className={sectionHeadingCss}>Crafting Skills</h3>
      <p className="text-gray-700 dark:text-gray-300">
        {locationGem.crafting_skills.map((skill) => skill.name).join(', ')}
      </p>
    </div>
  ) : null;

  const atonementSection = hasAtonement ? (
    <Dl>
      {locationGem.monster_atonement !== null && (
        <Fragment>
          <Dt>Monster Atonement</Dt>
          <Dd>{gemTypeLabel(locationGem.monster_atonement)}</Dd>
        </Fragment>
      )}
      {hasMeaningfulAtonementRange && locationGem.monster_atonement_range && (
        <Fragment>
          <Dt>Monster Atonement Range</Dt>
          <Dd>{formatPercentRange(locationGem.monster_atonement_range)}</Dd>
        </Fragment>
      )}
    </Dl>
  ) : null;

  const generatedGemWorldSection =
    hasGeneratedGemWorld && locationGem.generated_gem_world ? (
      <div>
        <h3 className={sectionHeadingCss}>Generated Gem World</h3>
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
      </div>
    ) : null;

  const sections: (ReactNode | null)[] = [
    identitySection,
    ...rangeGroupSections,
    craftingSkillsSection,
    atonementSection,
    generatedGemWorldSection,
  ];

  const visibleSections = sections.filter(
    (section): section is ReactNode => section !== null
  );

  if (isSidePeek) {
    return (
      <div className="flex flex-col gap-5">
        {visibleSections.map((section, index) => (
          <Fragment key={index}>
            {index > 0 && <Separator />}
            {section}
          </Fragment>
        ))}
      </div>
    );
  }

  return (
    <Card>
      <DetailGrid>
        {visibleSections.map((section, index) => (
          <Fragment key={index}>
            {section}
            {index % 2 === 1 && index !== visibleSections.length - 1 && (
              <Separator additional_css="col-span-full my-1" />
            )}
          </Fragment>
        ))}
      </DetailGrid>
    </Card>
  );
};

export default LocationGemDetailBody;
