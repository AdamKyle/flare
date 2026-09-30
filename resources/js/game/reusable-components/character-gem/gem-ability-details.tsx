import React, { ReactNode } from 'react';

import GameGemAbilityDefinition from '../../api-definitions/gems/game-gem-ability-definition';
import { gemAbilityEffectTypeLabel } from '../gem-ability/enums/gem-ability-effect-type';
import { gemAbilityScalingSourceLabel } from '../gem-ability/enums/gem-ability-scaling-source';
import {
  gemAbilityTypeHelp,
  gemAbilityTypeLabel,
} from '../gem-ability/enums/gem-ability-type';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

interface GemAbilityDetailsProps {
  ability: GameGemAbilityDefinition;
}

const GemAbilityDetails = ({ ability }: GemAbilityDetailsProps): ReactNode => {
  const typeLabel = gemAbilityTypeLabel(ability.ability_type);
  const procChance =
    ability.proc_chance !== null ? formatPercent(ability.proc_chance) : null;
  const effectValue = formatPercent(ability.effect_value);
  const scalingSource = ability.scaling_source
    ? gemAbilityScalingSourceLabel(ability.scaling_source)
    : null;

  const renderTriggersOn = (): ReactNode => {
    if (ability.attack_types.length === 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <GeneralToolTip
            label="Triggers on"
            message="While this gem is socketed into your gear, its effect can automatically trigger when you use one of these attack types during Exploration, Delve, Faction Loyalty Automation, or any form of manual combat."
            placement="auto"
          />
          Triggers on
        </Dt>
        <Dd>{ability.attack_types.join(', ')}</Dd>
      </>
    );
  };

  const renderEffectScaling = (): ReactNode => {
    if (!scalingSource) {
      return (
        <>
          <Dt>
            <GeneralToolTip
              label="Effect Strength"
              message={`This gem applies its effect at ${effectValue}.`}
              placement="auto"
            />
            Effect Strength
          </Dt>
          <Dd>{effectValue}</Dd>
        </>
      );
    }

    return (
      <>
        <Dt>
          <GeneralToolTip
            label="Damage Scaling"
            message={`This gem uses ${effectValue} of your total ${scalingSource} as the basis for the damage dealt by its effect.`}
            placement="auto"
          />
          Damage Scaling
        </Dt>
        <Dd>
          {effectValue} of {scalingSource}
        </Dd>
      </>
    );
  };

  return (
    <section
      className="space-y-3"
      aria-labelledby={`gem-ability-${ability.id}`}
    >
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h4
          id={`gem-ability-${ability.id}`}
          className="text-glacier-900 dark:text-glacier-100 font-semibold"
        >
          {ability.name}
        </h4>
        <span className="text-danube-700 dark:text-danube-300 inline-flex items-center gap-1 text-sm font-medium">
          {typeLabel}
          <GeneralToolTip
            label={`${typeLabel} Gem ability`}
            message={gemAbilityTypeHelp(ability.ability_type)}
            placement="auto"
          />
        </span>
      </div>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        {ability.description}
      </p>
      <Dl>
        <Dt>Effect</Dt>
        <Dd>{gemAbilityEffectTypeLabel(ability.effect_type)}</Dd>
        {renderTriggersOn()}
        {procChance !== null ? (
          <>
            <Dt>
              <GeneralToolTip
                label="Proc Chance"
                message={`After a matching combat action completes, this ability has a ${procChance} chance to activate, provided both you and the monster are still alive.`}
                placement="auto"
              />
              Proc Chance
            </Dt>
            <Dd>{procChance}</Dd>
          </>
        ) : null}
        {renderEffectScaling()}
      </Dl>
    </section>
  );
};

export default GemAbilityDetails;
