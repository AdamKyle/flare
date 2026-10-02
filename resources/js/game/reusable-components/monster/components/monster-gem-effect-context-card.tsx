import React, { Fragment, ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import { MonsterGemEffectChangedValueDefinition } from '../api/definitions/monster-detail-definition';
import MonsterGemEffectContextCardProps from '../types/monster-gem-effect-context-card-props';

import {
  formatNumberWithCommas,
  formatPercent,
  formatRangeWithCommas,
} from 'game-utils/format-number';

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
  const neutralClassName = 'text-danube-700 dark:text-danube-300 font-semibold';

  if (
    displayType === 'range' ||
    typeof base !== 'number' ||
    typeof effective !== 'number'
  ) {
    return neutralClassName;
  }

  if (effective > base) {
    return 'text-de-york-600 dark:text-de-york-400 font-semibold';
  }

  if (effective < base) {
    return 'text-mango-tango-600 dark:text-mango-tango-400 font-semibold';
  }

  return neutralClassName;
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
  const renderSourceMultipliers = (
    source: (typeof context.sources)[number]
  ): ReactNode => {
    const showMonsterMultiplier = source.monster_multiplier > 1;
    const showRewardMultiplier = source.reward_multiplier > 1;
    const showReductionMultiplier =
      source.reduction_multiplier !== null && source.reduction_multiplier > 1;

    if (
      !showMonsterMultiplier &&
      !showRewardMultiplier &&
      !showReductionMultiplier
    ) {
      return null;
    }

    return (
      <span className="text-glacier-600 dark:text-glacier-400 ml-2 text-xs">
        {showMonsterMultiplier && `Monster ×${source.monster_multiplier} `}
        {showRewardMultiplier && `Rewards ×${source.reward_multiplier} `}
        {showReductionMultiplier && `Reduction ×${source.reduction_multiplier}`}
      </span>
    );
  };

  const renderSource = (
    source: (typeof context.sources)[number]
  ): ReactNode => {
    const isMapGem = source.type === 'map_gem';
    const onOpen = isMapGem
      ? navigation?.on_open_map_gem
      : navigation?.on_open_location_gem;

    return (
      <Fragment key={`${source.type}-${source.profile_id}`}>
        <Dt>{isMapGem ? 'Map Gem' : 'Location Gem'}</Dt>
        <Dd>
          <FactualLink
            id={source.profile_id}
            label={`${source.profile_name} — ${source.rolled_gem_name}`}
            on_click={onOpen}
          />
          {renderSourceMultipliers(source)}
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

  const renderGroupSeparator = (index: number): ReactNode => {
    const isOddColumn = index % 2 === 1;
    const isLastGroup = index === changedValueGroups.length - 1;

    if (singleColumn || !isOddColumn || isLastGroup) {
      return null;
    }

    return <Separator additional_css="col-span-full my-1" />;
  };

  const renderGroup = (group: ChangedValueGroup, index: number): ReactNode => (
    <Fragment key={group.heading}>
      <div>
        <h4 className="mb-1 text-xs font-semibold tracking-wide text-gray-800 uppercase dark:text-gray-200">
          {group.heading}
        </h4>
        <Dl>{group.changes.map(renderChange)}</Dl>
      </div>
      {renderGroupSeparator(index)}
    </Fragment>
  );

  const renderGroups = (): ReactNode => {
    if (changedValueGroups.length === 0) {
      return null;
    }

    return (
      <DetailGrid single_column={singleColumn}>
        {changedValueGroups.map(renderGroup)}
      </DetailGrid>
    );
  };

  const renderGameMap = (): ReactNode => {
    if (!context.game_map) {
      return null;
    }

    return (
      <Fragment>
        <Dt>Game Map</Dt>
        <Dd>
          <FactualLink
            id={context.game_map.id}
            label={context.game_map.name}
            on_click={navigation?.on_open_map}
          />
        </Dd>
      </Fragment>
    );
  };

  const renderLocation = (): ReactNode => {
    if (!context.location) {
      return null;
    }

    return (
      <Fragment>
        <Dt>Location</Dt>
        <Dd>
          <FactualLink
            id={context.location.id}
            label={context.location.name}
            on_click={navigation?.on_open_location}
          />
        </Dd>
      </Fragment>
    );
  };

  const renderCharacterPowerReduction = (): ReactNode => {
    if (context.character_power_reduction <= 0) {
      return null;
    }

    return (
      <Fragment>
        <Dt>Character Power Reduction</Dt>
        <Dd>{formatPercent(context.character_power_reduction)}</Dd>
      </Fragment>
    );
  };

  const renderContextIdentity = (): ReactNode => {
    if (!hasContextIdentity) {
      return null;
    }

    return (
      <Dl>
        {renderGameMap()}
        {renderLocation()}
        {context.sources.map(renderSource)}
        {renderCharacterPowerReduction()}
      </Dl>
    );
  };

  const renderIdentitySeparator = (): ReactNode => {
    if (!hasContextIdentity || changedValueGroups.length === 0) {
      return null;
    }

    return <Separator additional_css="my-0" />;
  };

  return (
    <div>
      <h3 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        {context.label}
      </h3>

      {renderContextIdentity()}

      {renderIdentitySeparator()}

      {renderGroups()}
    </div>
  );
};

export default MonsterGemEffectContextCard;
