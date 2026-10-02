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

  const firePenetration = monster.fire_penetration ?? 0;
  const icePenetration = monster.ice_penetration ?? 0;
  const waterPenetration = monster.water_penetration ?? 0;

  const allZero =
    isNilOrZeroValue(fire) && isNilOrZeroValue(water) && isNilOrZeroValue(ice);

  const hasPenetration =
    firePenetration > 0 || icePenetration > 0 || waterPenetration > 0;

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
            'Reduces incoming Fire elemental damage. Matching Fire Penetration lowers the Fire Atonement used to resist that attack. Elemental strengths and weaknesses are calculated separately.'
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
            'Reduces incoming Water elemental damage. Matching Water Penetration lowers the Water Atonement used to resist that attack. Elemental strengths and weaknesses are calculated separately.'
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
            'Reduces incoming Ice elemental damage. Matching Ice Penetration lowers the Ice Atonement used to resist that attack. Elemental strengths and weaknesses are calculated separately.'
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

  const renderPenetration = () => {
    if (!hasPenetration) {
      return null;
    }

    return (
      <>
        <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
          Elemental Penetration
        </h3>
        <Separator />
        <Dl>
          <Dt>
            {renderLabel(
              'Fire Penetration',
              'Fire Penetration',
              "Reduces the target's Fire Atonement when resolving Fire elemental damage."
            )}
          </Dt>
          <Dd>{formatPercent(firePenetration)}</Dd>
          <Dt>
            {renderLabel(
              'Ice Penetration',
              'Ice Penetration',
              "Reduces the target's Ice Atonement when resolving Ice elemental damage."
            )}
          </Dt>
          <Dd>{formatPercent(icePenetration)}</Dd>
          <Dt>
            {renderLabel(
              'Water Penetration',
              'Water Penetration',
              "Reduces the target's Water Atonement when resolving Water elemental damage."
            )}
          </Dt>
          <Dd>{formatPercent(waterPenetration)}</Dd>
        </Dl>
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
      {renderPenetration()}
    </>
  );
};

export default MonsterElementalAtonementSection;
