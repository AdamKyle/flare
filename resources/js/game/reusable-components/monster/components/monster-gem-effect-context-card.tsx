import React, { Fragment, ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
  formatRangeWithCommas,
} from '../../../util/format-number';
import FactualLink from '../../quest-item/partials/factual-link';
import { MonsterGemEffectChangedValueDefinition } from '../api/definitions/monster-detail-definition';
import MonsterGemEffectContextCardProps from '../types/monster-gem-effect-context-card-props';

import DetailGrid from 'ui/detail-grid/detail-grid';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const CORE_COMBAT_FIELDS = new Set([
  'str',
  'dur',
  'dex',
  'chr',
  'int',
  'agi',
  'focus',
  'ac',
]);

const DAMAGE_HEALING_FIELDS = new Set([
  'health_range',
  'attack_range',
  'max_healing',
]);

const ACCURACY_EVASION_FIELDS = new Set([
  'accuracy',
  'dodge',
  'criticality',
  'casting_accuracy',
  'spell_evasion',
]);

const AMBUSH_COUNTER_FIELDS = new Set([
  'ambush_chance',
  'ambush_resistance_chance',
  'counter_chance',
  'counter_resistance_chance',
]);

const RESISTANCE_FIELDS = new Set(['affix_resistance']);

interface ChangedValueGroup {
  heading: string;
  changes: MonsterGemEffectChangedValueDefinition[];
}

const groupChangedValues = (
  changedValues: MonsterGemEffectChangedValueDefinition[]
): ChangedValueGroup[] => {
  const groups: ChangedValueGroup[] = [
    { heading: 'Core Combat Changes', changes: [] },
    { heading: 'Damage & Healing Changes', changes: [] },
    { heading: 'Accuracy & Evasion Changes', changes: [] },
    { heading: 'Ambush & Counter Changes', changes: [] },
    { heading: 'Resistance Changes', changes: [] },
    { heading: 'Spells / Affixes / Other Gem Changes', changes: [] },
  ];

  changedValues.forEach((change) => {
    if (CORE_COMBAT_FIELDS.has(change.field)) {
      groups[0].changes.push(change);
    } else if (DAMAGE_HEALING_FIELDS.has(change.field)) {
      groups[1].changes.push(change);
    } else if (ACCURACY_EVASION_FIELDS.has(change.field)) {
      groups[2].changes.push(change);
    } else if (AMBUSH_COUNTER_FIELDS.has(change.field)) {
      groups[3].changes.push(change);
    } else if (RESISTANCE_FIELDS.has(change.field)) {
      groups[4].changes.push(change);
    } else {
      groups[5].changes.push(change);
    }
  });

  return groups;
};

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

const renderChange = (
  change: MonsterGemEffectChangedValueDefinition
): ReactNode => {
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
};

const MonsterGemEffectContextCard = ({
  context,
  navigation,
  single_column: singleColumn,
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

  const changedValueGroups = groupChangedValues(context.changed_values).filter(
    (group) => group.changes.length > 0
  );

  const renderGroups = (): ReactNode => {
    if (changedValueGroups.length === 0) {
      return null;
    }

    return (
      <DetailGrid single_column={singleColumn}>
        {changedValueGroups.map((group, index) => (
          <Fragment key={group.heading}>
            <div>
              <h4 className="mb-1 text-xs font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200">
                {group.heading}
              </h4>
              <Dl>{group.changes.map(renderChange)}</Dl>
            </div>
            {!singleColumn &&
              index % 2 === 1 &&
              index !== changedValueGroups.length - 1 && (
                <Separator additional_css="col-span-full my-1" />
              )}
          </Fragment>
        ))}
      </DetailGrid>
    );
  };

  return (
    <div>
      <h3 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
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

      {hasContextIdentity && changedValueGroups.length > 0 && (
        <Separator additional_css="my-0" />
      )}

      {renderGroups()}
    </div>
  );
};

export default MonsterGemEffectContextCard;
