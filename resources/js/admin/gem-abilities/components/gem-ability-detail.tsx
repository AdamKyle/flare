import React, { ReactNode } from 'react';

import GemAbilityDetailProps from './types/gem-ability-detail-props';
import { attackTypeLabel } from '../../../game/reusable-components/class-mastery/enums/attack-type';
import { gemAbilityEffectTypeLabel } from '../../../game/reusable-components/gem-ability/enums/gem-ability-effect-type';
import { gemAbilityScalingSourceLabel } from '../../../game/reusable-components/gem-ability/enums/gem-ability-scaling-source';
import { gemAbilityTypeLabel } from '../../../game/reusable-components/gem-ability/enums/gem-ability-type';
import { isActiveAbilityType } from '../utils/gem-ability-form-state';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const formatPercent = (value: number): string => `${(value * 100).toFixed(2)}%`;

const GemAbilityDetail = ({
  gem_ability: gemAbility,
}: GemAbilityDetailProps): ReactNode => {
  const isActive = isActiveAbilityType(gemAbility.ability_type);
  const allowedAttackTypes = gemAbility.attack_types
    .map(attackTypeLabel)
    .join(', ');

  const renderActiveDetails = (): ReactNode => {
    if (!isActive) {
      return null;
    }

    return (
      <>
        <Dt>Proc Chance</Dt>
        <Dd>
          {gemAbility.proc_chance === null
            ? 'N/A'
            : formatPercent(gemAbility.proc_chance)}
        </Dd>
        <Dt>Scaling Source</Dt>
        <Dd>
          {gemAbility.scaling_source === null
            ? 'N/A'
            : gemAbilityScalingSourceLabel(gemAbility.scaling_source)}
        </Dd>
      </>
    );
  };

  return (
    <Dl>
      <Dt>Name</Dt>
      <Dd>{gemAbility.name}</Dd>
      <Dt>Description</Dt>
      <Dd>{gemAbility.description}</Dd>
      <Dt>Ability Type</Dt>
      <Dd>{gemAbilityTypeLabel(gemAbility.ability_type)}</Dd>
      <Dt>Effect Type</Dt>
      <Dd>{gemAbilityEffectTypeLabel(gemAbility.effect_type)}</Dd>
      <Dt>Allowed Attack Types</Dt>
      <Dd>{allowedAttackTypes}</Dd>
      <Dt>Effect Value</Dt>
      <Dd>{formatPercent(gemAbility.effect_value)}</Dd>
      {renderActiveDetails()}
      <Dt>Enabled</Dt>
      <Dd>{gemAbility.enabled ? 'Yes' : 'No'}</Dd>
    </Dl>
  );
};

export default GemAbilityDetail;
