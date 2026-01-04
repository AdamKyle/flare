import React, { ReactNode } from 'react';

import AncestralItemSkillSection from './sections/ancestral-item-skill-section';
import ClassBonusAttributesSection from './sections/class-bonus-attributes-section';
import ClassMasteriesSection from './sections/class-masteries-section';
import ClassSpecialtiesSection from './sections/class-specialties-section';
import AttackTypesBreakDownProps from './types/attack-types-break-down-props';
import { getAttackTypeFormattedName } from '../../../enums/attack-types';
import { StatTypes } from '../../../enums/stat-types';
import EquippedItems from '../../character-stat-types/partials/equipped-items';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const Health = ({ break_down, type }: AttackTypesBreakDownProps): ReactNode => {
  return (
    <div>
      <div className={'mx-auto w-full md:w-2/3'}>
        <Dl>
          <Dt>
            <strong>Durability</strong>:
          </Dt>
          <Dd>{formatNumberWithCommas(break_down.regular.stat_amount)}</Dd>
        </Dl>
        <p className="my-4">
          Below is a break down of your{' '}
          <strong>{getAttackTypeFormattedName(type)}</strong>. When it comes to
          survivability theres two forms: Be a glass cannon and pray you kill
          the creature in one turn, or buff up and survive the onslaught to
          greet you. Everything below is a complete break down of what goes into
          your health. Your durability stat above, is the modded version of your
          durability, the more durability, the more health.
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
            <AncestralItemSkillSection
              ancestral_item_skill_data={
                break_down.regular.ancestral_item_skill_data
              }
            />
            <ClassBonusAttributesSection
              class_bonus_details={break_down.regular.class_bonus_details}
            />
            <ClassMasteriesSection
              class_masteries={break_down.regular.masteries}
            />
            <ClassSpecialtiesSection
              class_specialties={break_down.regular.class_specialties}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default Health;
