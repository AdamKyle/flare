import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatPercent } from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterElementalAtonementSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  const fire = monster.fire_atonement ?? 0;
  const water = monster.water_atonement ?? 0;
  const ice = monster.ice_atonement ?? 0;

  const allZero =
    isNilOrZeroValue(fire) && isNilOrZeroValue(water) && isNilOrZeroValue(ice);

  if (allZero) {
    return null;
  }

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

  const renderFire = () => {
    if (isNilOrZeroValue(fire)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Fire Atonement',
            'Fire Atonement',
            'When this monster is atoned to Fire, a percentage of its attack is dealt as fire damage. Equip Water-based gems to resist fire damage, and rely on strong Armour to mitigate the rest.'
          )}
        </Dt>
        <Dd>{formatPercent(fire)}</Dd>
      </>
    );
  };

  const renderWater = () => {
    if (isNilOrZeroValue(water)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Water Atonement',
            'Water Atonement',
            'When this monster is atoned to Water, a percentage of its attack is dealt as water damage. Equip Ice-based gems to resist water damage, and rely on strong Armour to mitigate the rest.'
          )}
        </Dt>
        <Dd>{formatPercent(water)}</Dd>
      </>
    );
  };

  const renderIce = () => {
    if (isNilOrZeroValue(ice)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Ice Atonement',
            'Ice Atonement',
            'When this monster is atoned to Ice, a percentage of its attack is dealt as ice damage. Equip Fire-based gems to resist ice damage, and rely on strong Armour to mitigate the rest.'
          )}
        </Dt>
        <Dd>{formatPercent(ice)}</Dd>
      </>
    );
  };

  const renderAtonedToElement = () => {
    let elementName = 'Fire';
    let elementValue = fire;

    if (water > elementValue) {
      elementName = 'Water';
      elementValue = water;
    }

    if (ice > elementValue) {
      elementName = 'Ice';
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Atoned to Element',
            'Atoned to Element',
            `This enemy is atoned to: ${elementName} and will deal ${elementName.toLowerCase()} damage.`
          )}
        </Dt>
        <Dd>{elementName}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Elemental Atonement
      </h3>
      <Separator />
      <Dl>
        {renderFire()}
        {renderWater()}
        {renderIce()}
        {renderAtonedToElement()}
      </Dl>
    </>
  );
};

export default MonsterElementalAtonementSection;
