import React from 'react';

import AttackTypesBreakDownProps from './types/attack-types-break-down-props';
import { getAttackTypeFormattedName } from '../../../enums/attack-types';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const RingDamage = ({ break_down, type }: AttackTypesBreakDownProps) => {
  return (
    <div>
      <div className={'mx-auto w-full md:w-2/3'}>
        <Dl>
          <Dt>
            <strong>Base Damage Value</strong>:
          </Dt>
          <Dd>{formatNumberWithCommas(break_down.regular.base_damage)}</Dd>
        </Dl>
        <p className="my-4">
          Below is a break down of your{' '}
          <strong>{getAttackTypeFormattedName(type)}</strong>. Rings dont
          increase their damage based on your gear, their damage is fixed. But
          rings offer so much more such as spell evasion, reducing the enemies
          healing amount as well as their affix damage. Rings may seem weak, but
          they can be enchanted to affect other aspects such as your stats or
          your damage. Dont skimp out on rings my friend.
        </p>
        <Separator />
      </div>
      <div className={'mx-auto w-full md:w-2/3'}>
        <div className={'text-center'}>
          <h4>Ring Effects</h4>
        </div>
        <Separator />
        <div className={'my-2'}>
          <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
            <li>
              <strong>Spell Evasion</strong>{' '}
              <span className="text-green-700 dark:text-green-500">
                +{(break_down.regular.spell_evasion * 100).toFixed(2)}%
              </span>
            </li>
            <li>
              <strong>Affix Damage Reduction</strong>{' '}
              <span className="text-green-700 dark:text-green-500">
                +{(break_down.regular.affix_damage_reduction * 100).toFixed(2)}%
              </span>
            </li>
            <li>
              <strong>Enemy Healing Reduction</strong>{' '}
              <span className="text-green-700 dark:text-green-500">
                +{(break_down.regular.healing_reduction * 100).toFixed(2)}%
              </span>
            </li>
          </ol>
        </div>
      </div>
    </div>
  );
};

export default RingDamage;
