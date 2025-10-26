import React from 'react';

import MonsterCoreSectionProps from '../types/partials/monster-core-section-props';
import { getLocationTypeName } from '../util/get-location-type-name';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterCoreSection = (props: MonsterCoreSectionProps) => {
  const { monster } = props;

  const renderLabel = (
    label: string,
    tooltipLabel?: string,
    tooltipMessage?: string
  ) => {
    const hasTip =
      typeof tooltipLabel !== 'undefined' &&
      typeof tooltipMessage !== 'undefined';

    if (hasTip) {
      return (
        <div className="flex items-center gap-2">
          <span className="inline-flex w-4 shrink-0 justify-center">
            <GeneralToolTip label={tooltipLabel} message={tooltipMessage} />
          </span>
          <span className="whitespace-nowrap">{label}:</span>
        </div>
      );
    }

    return (
      <div className="flex items-center gap-2">
        <span className="inline-flex w-4 shrink-0 justify-center" />
        <span className="whitespace-nowrap">{label}:</span>
      </div>
    );
  };

  const renderSpecialLocation = () => {
    if (monster.only_for_location_type === null) {
      return null;
    }

    const locationName = getLocationTypeName(monster.only_for_location_type);

    return (
      <>
        <Dt>
          {renderLabel(
            'Can only be fought at',
            'Can only be fought at',
            'In order to fight this monster you should be at this location on the map the monster is from, listed above.'
          )}
        </Dt>
        <Dd>{locationName}</Dd>
      </>
    );
  };

  const renderCelestialCosts = () => {
    if (!monster.is_celestial_entity) {
      return null;
    }

    const goldCost =
      typeof monster.gold_cost === 'number'
        ? formatNumberWithCommas(monster.gold_cost)
        : '—';

    const goldDustCost =
      typeof monster.gold_dust_cost === 'number'
        ? formatNumberWithCommas(monster.gold_dust_cost)
        : '—';

    return (
      <>
        <Dt>{renderLabel('Conjuration Cost (Gold)')}</Dt>
        <Dd>{goldCost}</Dd>

        <Dt>{renderLabel('Conjuration Cost (Gold Dust)')}</Dt>
        <Dd>{goldDustCost}</Dd>
      </>
    );
  };

  return (
    <Dl>
      <Dt>{renderLabel('Monster Name')}</Dt>
      <Dd>{monster.name}</Dd>

      <Dt>{renderLabel('Lives on map')}</Dt>
      <Dd>{monster.map_name}</Dd>

      {renderSpecialLocation()}

      <Dt>
        {renderLabel(
          'Base Damage Stat',
          'Base Damage Stat',
          'Much like characters, this is the stat the monster uses when they attack, a portion of this stat is added to their over all attack.'
        )}
      </Dt>
      <Dd>{monster.damage_stat}</Dd>

      <Dt>
        {renderLabel(
          'Receive 1/3 Xp at level',
          'Receive 1/3 Xp at level',
          'When your character is or higher then this level you will recieve 1/3rd of the monsters xp as a reward.'
        )}
      </Dt>
      <Dd>{formatNumberWithCommas(monster.max_level)}</Dd>

      {renderCelestialCosts()}
    </Dl>
  );
};

export default MonsterCoreSection;
