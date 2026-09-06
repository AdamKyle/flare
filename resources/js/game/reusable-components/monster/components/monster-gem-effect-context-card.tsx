import React, { Fragment, ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
  formatRangeWithCommas,
} from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import MonsterGemEffectContextCardProps from '../types/monster-gem-effect-context-card-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

/**
 * Render one cached Gem effect context (a normal Map, a Gem-bearing
 * Location, a Map Gem World, or a Location Gem World) for the Monster
 * Special Location Effects tab. Every value is read from the cached
 * effective Monster payload; nothing here recalculates Gem math.
 */
const MonsterGemEffectContextCard = ({
  context,
  navigation,
}: MonsterGemEffectContextCardProps): ReactNode => {
  const renderChangedValue = (
    value: string | number | null,
    displayType: 'number' | 'range' | 'percent'
  ): ReactNode => {
    if (value === null) {
      return null;
    }

    if (displayType === 'percent' && typeof value === 'number') {
      return formatPercent(value);
    }

    if (displayType === 'range' && typeof value === 'string') {
      return formatRangeWithCommas(value);
    }

    if (typeof value === 'number') {
      return formatNumberWithCommas(value);
    }

    return value;
  };

  const renderSource = (
    source: (typeof context.sources)[number]
  ): ReactNode => {
    const isMapGem = source.type === 'map_gem';
    const onOpen = isMapGem
      ? navigation?.on_open_map_gem
      : navigation?.on_open_location_gem;
    const showMonsterMultiplier = source.monster_multiplier > 1;
    const showRewardMultiplier = source.reward_multiplier > 1;
    const showReductionMultiplier =
      source.reduction_multiplier !== null && source.reduction_multiplier > 1;

    return (
      <Fragment key={`${source.type}-${source.profile_id}`}>
        <Dt>{isMapGem ? 'Map Gem' : 'Location Gem'}</Dt>
        <Dd>
          <FactualLink
            id={source.profile_id}
            label={`${source.profile_name} — ${source.rolled_gem_name}`}
            on_click={onOpen}
          />
          {(showMonsterMultiplier ||
            showRewardMultiplier ||
            showReductionMultiplier) && (
            <span className="text-glacier-600 dark:text-glacier-400 ml-2 text-xs">
              {showMonsterMultiplier &&
                `Monster ×${source.monster_multiplier} `}
              {showRewardMultiplier && `Rewards ×${source.reward_multiplier} `}
              {showReductionMultiplier &&
                `Reduction ×${source.reduction_multiplier}`}
            </span>
          )}
        </Dd>
      </Fragment>
    );
  };

  return (
    <Card>
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        {context.label}
      </h3>
      <Dl>
        {context.game_map && (
          <Fragment>
            <Dt>Game Map</Dt>
            <Dd>{context.game_map.name}</Dd>
          </Fragment>
        )}
        {context.location && (
          <Fragment>
            <Dt>Location</Dt>
            <Dd>{context.location.name}</Dd>
          </Fragment>
        )}
        {context.sources.map(renderSource)}
        {context.character_power_reduction > 0 && (
          <Fragment>
            <Dt>Character Power Reduction</Dt>
            <Dd>{formatPercent(context.character_power_reduction)}</Dd>
          </Fragment>
        )}
        {context.changed_values.map((change) => (
          <Fragment key={change.field}>
            <Dt>{change.label}</Dt>
            <Dd>
              {renderChangedValue(change.base_value, change.display_type)}
              {' → '}
              {renderChangedValue(change.effective_value, change.display_type)}
            </Dd>
          </Fragment>
        ))}
      </Dl>
    </Card>
  );
};

export default MonsterGemEffectContextCard;
