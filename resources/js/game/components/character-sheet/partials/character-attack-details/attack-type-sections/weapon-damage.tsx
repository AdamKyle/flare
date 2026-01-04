import React, { ReactNode } from 'react';

import AttackTypesBreakDownProps from './types/attack-types-break-down-props';
import { getAttackTypeFormattedName } from '../../../enums/attack-types';
import { StatTypes } from '../../../enums/stat-types';
import EquippedItems from '../../character-stat-types/partials/equipped-items';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const WeaponDamage = ({
  break_down,
  type,
}: AttackTypesBreakDownProps): ReactNode => {
  const renderAncestralSkillItemData = () => {
    const ancestralSkillItemData = break_down.regular.ancestral_item_skill_data;

    if (ancestralSkillItemData.length <= 0) {
      return null;
    }

    const listElements = ancestralSkillItemData.map((ancestralSkillInfo) => {
      return (
        <li>
          <strong>{ancestralSkillInfo.name}</strong>{' '}
          <span className="text-green-700 dark:text-green-500">
            +{(ancestralSkillInfo.increase_amount * 100).toFixed(2)}%
          </span>
        </li>
      );
    });

    return (
      <div className={'my-2'}>
        <h4
          className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}
        >
          Ancestral Item Skill Effects
        </h4>
        <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
          {listElements}
        </ol>
      </div>
    );
  };

  const renderClassMasteries = () => {
    const classMasteries = break_down.regular.masteries;

    if (!classMasteries || classMasteries.length <= 0) {
      return null;
    }

    const listElements = classMasteries.map((classMastery) => {
      return (
        <li>
          <strong>{classMastery.name}</strong>{' '}
          <span className="text-green-700 dark:text-green-500">
            +{(classMastery.amount * 100).toFixed(2)}%
          </span>
        </li>
      );
    });

    return (
      <div className={'my-2'}>
        <h4
          className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}
        >
          Class Masteries
        </h4>
        <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
          {listElements}
        </ol>
      </div>
    );
  };

  const renderClassBonusAttribute = () => {
    const classBonusDetails = break_down.regular.class_bonus_details;

    if (!classBonusDetails) {
      return null;
    }

    return (
      <div className={'mb-2'}>
        <h4
          className={'text-marigold-700 dark:text-marigold-500 my-2 font-bold'}
        >
          Class Bonus Attributes
        </h4>
        <ol className="ml-2 list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
          <li>
            <strong>{classBonusDetails.name}</strong>{' '}
            <span className="text-green-700 dark:text-green-500">
              +{(classBonusDetails.amount * 100).toFixed(2)}%
            </span>
          </li>
        </ol>
      </div>
    );
  };

  return (
    <div>
      <div className={'mx-auto w-full md:w-2/3'}>
        <Dl>
          <Dt>
            <strong>Base Damage Value</strong>:
          </Dt>
          <Dd>{formatNumberWithCommas(break_down.regular.base_damage)}</Dd>
          <Dt>
            <strong>Damage Stat</strong>:
          </Dt>
          <Dd>{break_down.regular.damage_stat_name}</Dd>
          <Dt>
            <strong>Damage Stat Amount</strong>:
          </Dt>
          <Dd>
            {formatNumberWithCommas(break_down.regular.damage_stat_amount)}
          </Dd>
          <Dt>
            <strong>Percentage of stat used towards damage</strong>
          </Dt>
          <Dd>
            {(break_down.regular.percentage_of_stat_used * 100).toFixed(2)}%
          </Dd>
        </Dl>
        <p className="my-4">
          Below is a break down of your{' '}
          <strong>{getAttackTypeFormattedName(type)}</strong>. This is your
          total damage and everything you have equipped, enchanted and so on.
          When it comes to fighting you ability to kill the creature in one hit
          is more paramount that your ability to survive. Why delay the rewards
          when you can increase this value, min max it and break the threshold.
          There is no cap on damage and everything plays apart of it, how high
          can you make it go?
        </p>
        <Separator />
      </div>
      <div className={'w-full'}>
        <div className={'grid-cols-0 grid gap-2 md:grid-cols-2'}>
          <div>
            <div className={'text-center'}>
              <h4>Gear affecting this stat</h4>
            </div>
            <Separator />
            <ol className="list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
              <EquippedItems
                items_equipped={break_down.regular.items_equipped}
                stat_type={StatTypes.BASE_DAMAGE}
              />
            </ol>
          </div>
          <div>
            <div className={'text-center'}>
              <h4>Other Enhancements/Afflictions</h4>
            </div>
            <Separator />
            {renderAncestralSkillItemData()}
            {renderClassBonusAttribute()}
            {renderClassMasteries()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default WeaponDamage;
