import { isNil } from 'lodash';
import React from 'react';

import StatInfoToolTip from '../../../../../reusable-components/item/stat-info-tool-tip';
import {
  formatPercent,
  formatSignedPercent,
} from '../../../../../util/format-number';
import AttachedAffixDetailsProps from '../types/attached-affix-details-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const AttachedAffixDetails = ({
  affix,
  on_close,
}: AttachedAffixDetailsProps) => {
  const buildAttributeTooltip = (attributeLabel: string) => {
    return `This ${attributeLabel.toLowerCase()} is applied directly to the item's ${attributeLabel.toLowerCase()}, which in turn is applied to the character. This value stacks additively with other affixes; the combined total is applied to the item's ${attributeLabel.toLowerCase()}.`;
  };

  const renderAffixHeader = () => {
    return (
      <div>
        <h2 className="text-lg my-2 text-gray-800 dark:text-gray-300">
          {affix.name}
        </h2>
        <Separator />
        <p className="my-4 text-gray-800 dark:text-gray-300">
          {affix.description}
        </p>
        <Separator />
      </div>
    );
  };

  const renderStatRow = (label: string, value: number | null) => {
    if (isNil(value) || value === 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={buildAttributeTooltip(label)}
              value={value}
              renderAsPercent
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600 shrink-0"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderCoreModRow = (label: string, value: number | null) => {
    if (isNil(value)) {
      return null;
    }

    const numericValue = Number(value);
    if (numericValue <= 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={buildAttributeTooltip(label)}
              value={numericValue}
              renderAsPercent
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600 shrink-0"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatSignedPercent(numericValue)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderNegativePercentRow = (
    displayLabel: string,
    attributeLabel: string,
    value: number
  ) => {
    if (value <= 0) return null;

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={`Reduces the enemy's ${attributeLabel.toLowerCase()} by ${formatPercent(
                value
              )}.`}
              value={value}
              renderAsPercent
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{displayLabel}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-down text-rose-600 shrink-0"
              aria-hidden="true"
            />
            <span className="font-semibold text-rose-600 tabular-nums">
              -{formatPercent(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderSimplePercentRow = (
    label: string,
    tooltip: string,
    value: number
  ) => {
    if (value <= 0) return null;

    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={tooltip}
              value={value}
              renderAsPercent
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600 shrink-0"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700 tabular-nums">
              {formatPercent(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderInfoRow = (label: string, tooltip: string, valueText: string) => {
    return (
      <>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatInfoToolTip
              label={tooltip}
              value={0}
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <span className="font-semibold text-gray-700 dark:text-gray-200">
              {valueText}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderStatsSection = () => {
    const statModifiers = [
      { label: 'Strength', value: affix.str_mod },
      { label: 'Dexterity', value: affix.dex_mod },
      { label: 'Intelligence', value: affix.int_mod },
      { label: 'Charisma', value: affix.chr_mod },
      { label: 'Agility', value: affix.agi_mod },
      { label: 'Durability', value: affix.dur_mod },
      { label: 'Focus', value: affix.focus_mod },
    ];

    const allStatModifiersZero = statModifiers.every(
      ({ value }) => Number(value ?? 0) === 0
    );
    if (allStatModifiersZero) return null;

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Stats
        </h4>
        <Dl>
          {statModifiers.map(({ label, value }) => renderStatRow(label, value))}
        </Dl>
      </div>
    );
  };

  const renderCoreAttributesSection = () => {
    const baseDamageMod = Number(affix.base_damage_mod ?? 0);
    const baseAcMod = Number(affix.base_ac_mod ?? 0);
    const baseHealingMod = Number(affix.base_healing_mod ?? 0);

    const allCoreAttributesZero =
      baseDamageMod <= 0 && baseAcMod <= 0 && baseHealingMod <= 0;
    if (allCoreAttributesZero) return null;

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Core Attributes
        </h4>
        <Dl>
          {renderCoreModRow('Base Damage Mod', affix.base_damage_mod)}
          {renderCoreModRow('Base AC Mod', affix.base_ac_mod)}
          {renderCoreModRow('Base Healing Mod', affix.base_healing_mod)}
        </Dl>
      </div>
    );
  };

  const renderAffixDamageSection = () => {
    const damageAmount = Number(affix.damage_amount ?? 0);
    if (damageAmount <= 0) {
      return null;
    }

    const damageTooltip = `When you attack an enemy with any attack option, we use ${formatPercent(
      damageAmount
    )} of your weapon damage to deal additional damage. This is known as affix damage.`;

    const stackingTooltip = affix.damage_can_stack
      ? 'The damage value above stacks additively with other affixes that also provide stackable damage, increasing the percent of your weapon damage applied. This can exceed 100%.'
      : 'This damage does not stack with other affixes that provide damage. We take the highest value among your non-stacking damage affixes.';

    const irresistibleTooltip = affix.irresistible_damage
      ? 'This damage is irresistible; the enemy cannot resist it.'
      : 'This damage is resistible; the enemy can resist it.';

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Affix Damage
        </h4>
        <Dl>
          {renderSimplePercentRow('Affix Damage', damageTooltip, damageAmount)}
          {renderInfoRow(
            'Damage Stacking',
            stackingTooltip,
            affix.damage_can_stack ? 'Stacks' : 'Does Not Stack'
          )}
          {renderInfoRow(
            'Irresistible',
            irresistibleTooltip,
            affix.irresistible_damage ? 'Yes' : 'No'
          )}
        </Dl>
      </div>
    );
  };

  const renderSkillModifiersSection = () => {
    const skillBonus = Number(affix.skill_bonus ?? 0);
    const skillTrainingBonus = Number(affix.skill_training_bonus ?? 0);

    if (skillBonus <= 0 && skillTrainingBonus <= 0) return null;

    const skillBonusTooltip = `This is applied directly to the specified skill when you use it. It increases your chance of success for that skill by ${formatPercent(
      skillBonus
    )}. This stacks additively with other affixes that affect the same skill.`;

    const skillTrainingBonusTooltip = `This is applied directly to the XP you gain from training or using the skill. Whenever you earn XP for the skill, you gain ${formatPercent(
      skillTrainingBonus
    )} more. This stacks additively with other affixes that affect this skill.`;

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Skill Modifiers
        </h4>
        <Dl>
          <Dt>Skill Name:</Dt>
          <Dd>{affix.skill_name}</Dd>
          {renderSimplePercentRow('Skill Bonus', skillBonusTooltip, skillBonus)}
          {renderSimplePercentRow(
            'Skill Training Bonus',
            skillTrainingBonusTooltip,
            skillTrainingBonus
          )}
        </Dl>
      </div>
    );
  };

  const renderMiscSection = () => {
    const lifeStealAmount = Number(affix.steal_life_amount ?? 0);
    const devouringLight = Number(affix.devouring_light ?? 0);
    const entrancedChance = Number(affix.entranced_chance ?? 0);
    const skillReduction = Number(affix.skill_reduction ?? 0);
    const resistanceReduction = Number(affix.resistance_reduction ?? 0);

    const everyZero =
      lifeStealAmount <= 0 &&
      devouringLight <= 0 &&
      entrancedChance <= 0 &&
      skillReduction <= 0 &&
      resistanceReduction <= 0;

    if (everyZero) return null;

    const lifeStealTooltip = `This steals ${formatPercent(
      lifeStealAmount
    )} of the enemy's health during your attack phase. Stacks additively across items up to 50% for non-vampire classes and up to 99% for Vampires.`;

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Misc. Modifiers
        </h4>
        <Dl>
          {renderSimplePercentRow(
            'Life Stealing',
            lifeStealTooltip,
            lifeStealAmount
          )}

          {renderSimplePercentRow(
            'Devouring Light',
            "This stacks additively and reduces the enemy's chance to void you, which would render you weak and vulnerable. (If an enemy voids you, all enchantments on your gear fail.)",
            devouringLight
          )}

          {renderSimplePercentRow(
            'Entrancing',
            'This stacks additively and increases the chance to entrance an enemy, preventing them from attacking you, ambushing, or countering you. This does not prevent their ability to void or devoid you.',
            entrancedChance
          )}

          {renderNegativePercentRow('Skill Reduction', 'Skill', skillReduction)}

          {renderNegativePercentRow(
            'Resistance Reduction',
            'Resistance',
            resistanceReduction
          )}
        </Dl>
      </div>
    );
  };

  const renderWithSeparator = (
    section: React.ReactNode,
    isLastSection?: boolean
  ) => {
    if (!section) return null;

    return (
      <>
        {section}
        {!isLastSection && <Separator />}
      </>
    );
  };

  return (
    <>
      <div className="text-center p-4">
        <Button
          on_click={on_close}
          label="Close"
          variant={ButtonVariant.SUCCESS}
        />
      </div>

      <div className="px-4 flex flex-col gap-4 pb-4">
        {renderAffixHeader()}

        <div className="space-y-4">
          {renderWithSeparator(renderStatsSection())}
          {renderWithSeparator(renderCoreAttributesSection())}
          {renderWithSeparator(renderAffixDamageSection())}
          {renderWithSeparator(renderSkillModifiersSection())}
          {renderWithSeparator(renderMiscSection(), true)}
        </div>
      </div>
    </>
  );
};

export default AttachedAffixDetails;
