import React, { Fragment, ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
  formatRangeWithCommas,
} from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import MonsterGemEffectContextCardProps from '../types/monster-gem-effect-context-card-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const formatValue = (
  value: string | number | null,
  displayType: 'number' | 'range' | 'percent'
): string => {
  if (value === null) {
    return '';
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

  return String(value);
};

const resolveEffectiveClassName = (
  base: string | number | null,
  effective: string | number | null,
  displayType: 'number' | 'range' | 'percent'
): string => {
  if (
    displayType === 'number' ||
    (displayType === 'percent' &&
      typeof base === 'number' &&
      typeof effective === 'number')
  ) {
    if (typeof base === 'number' && typeof effective === 'number') {
      if (effective > base) {
        return 'text-de-york-600 dark:text-de-york-400 font-semibold';
      }

      if (effective < base) {
        return 'text-mango-tango-600 dark:text-mango-tango-400 font-semibold';
      }
    }
  }

  return 'text-danube-700 dark:text-danube-300 font-semibold';
};

const MonsterGemEffectContextCard = ({
  context,
  navigation,
}: MonsterGemEffectContextCardProps): ReactNode => {
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

  const hasContextIdentity =
    Boolean(context.game_map) ||
    Boolean(context.location) ||
    context.sources.length > 0 ||
    context.character_power_reduction > 0;

  const hasChangedValues = context.changed_values.length > 0;

  return (
    <div>
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        {context.label}
      </h3>

      {hasContextIdentity && (
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
        </Dl>
      )}

      {hasContextIdentity && hasChangedValues && <Separator />}

      {hasChangedValues && (
        <Dl>
          {context.changed_values.map((change) => {
            const effectiveClassName = resolveEffectiveClassName(
              change.base_value,
              change.effective_value,
              change.display_type
            );

            return (
              <Fragment key={change.field}>
                <Dt>{change.label}</Dt>
                <Dd>
                  <span aria-hidden="true">
                    <span className="text-glacier-500 dark:text-glacier-400">
                      {formatValue(change.base_value, change.display_type)}
                    </span>
                    <span className="text-glacier-500 mx-1">→</span>
                    <span className={effectiveClassName}>
                      {formatValue(change.effective_value, change.display_type)}
                    </span>
                  </span>
                  <span className="sr-only">
                    {`Base ${formatValue(change.base_value, change.display_type)}, effective ${formatValue(change.effective_value, change.display_type)}`}
                  </span>
                </Dd>
              </Fragment>
            );
          })}
        </Dl>
      )}
    </div>
  );
};

export default MonsterGemEffectContextCard;
