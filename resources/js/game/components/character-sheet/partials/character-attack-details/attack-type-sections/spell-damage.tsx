import React, { ReactNode } from 'react';

import AncestralItemSkillSection from './sections/ancestral-item-skill-section';
import ClassBonusAttributesSection from './sections/class-bonus-attributes-section';
import ClassMasteriesSection from './sections/class-masteries-section';
import ClassSkillsSection from './sections/class-skills-section';
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

const SpellDamage = ({
  break_down,
  type,
}: AttackTypesBreakDownProps): ReactNode => {
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
            <strong>Equipped Damage</strong>:
          </Dt>
          <Dd>
            {formatNumberWithCommas(break_down.regular.total_damage_for_type)}
          </Dd>
          <Dt>
            <strong>Non Equipped Damage</strong>:
          </Dt>
          <Dd>
            {formatNumberWithCommas(
              break_down.regular.non_equipped_damage_amount
            )}
          </Dd>
          <Dt>
            <strong>Percentage of stat used towards damage</strong>
          </Dt>
          <Dd>
            {(break_down.regular.percentage_of_stat_used * 100).toFixed(2)}%
          </Dd>
          <Dt>
            <strong>Total Amount of stat used</strong>
          </Dt>
          <Dd>
            {formatNumberWithCommas(
              break_down.regular.spell_damage_stat_amount_to_use
            )}
          </Dd>
          <Dt>
            <strong>
              Percentage of stat used towards damage (when no spells equipped)
            </strong>
          </Dt>
          <Dd>
            {(
              break_down.regular.non_equipped_percentage_of_stat_used * 100
            ).toFixed(2)}
            %
          </Dd>
        </Dl>
        <p className="my-4">
          Below is a break down of your{' '}
          <strong>{getAttackTypeFormattedName(type)}</strong>. As we can see a
          lot goes into it. We have your int stat and a % of that stat which is
          higher from some classes like heretics and Arcane Alchemists, while
          others do poorly casting spells. but that doesnt mean they can't, you
          just have to raise that int. Spells are used during Cast, Cast and
          Attack or Attack and cast and can change the tide of the battle!
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
            <ClassSkillsSection
              class_skills={break_down.regular.skills_effecting_damage}
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

export default SpellDamage;
