import { isNil } from 'lodash';
import React from 'react';

import StatInfoToolTip from '../../../../../reusable-components/item/stat-info-tool-tip';
import {
  formatFloat,
  formatIntWithPlus,
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

const LABEL_WIDTH = 'w-56 sm:w-64';

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
        <h2 className="text-lg my-2">{affix.name}</h2>
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
          <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
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
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
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
          <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
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
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
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
    if (value <= 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
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
              className="fas fa-chevron-down text-rose-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-rose-600">
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
    if (value <= 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
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
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
              {formatPercent(value)}
            </span>
          </span>
        </Dd>
      </>
    );
  };

  const renderSimpleNumberRow = (
    label: string,
    tooltip: string,
    value: number
  ) => {
    if (value <= 0) {
      return null;
    }

    return (
      <>
        <Dt>
          <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
            <StatInfoToolTip
              label={tooltip}
              value={value}
              align="right"
              custom_message
            />
            <span className="min-w-0 break-words">{label}</span>
          </span>
        </Dt>
        <Dd>
          <span className="inline-flex items-center gap-2 whitespace-nowrap">
            <i
              className="fas fa-chevron-up text-emerald-600"
              aria-hidden="true"
            />
            <span className="font-semibold text-emerald-700">
              {formatIntWithPlus(value)}
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

    if (allStatModifiersZero) {
      return null;
    }

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

    if (allCoreAttributesZero) {
      return null;
    }

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

  const renderSkillModifiersSection = () => {
    const skillBonus = Number(affix.skill_bonus ?? 0);
    const skillTrainingBonus = Number(affix.skill_training_bonus ?? 0);

    if (skillBonus <= 0 && skillTrainingBonus <= 0) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Skill Modifiers
        </h4>
        <Dl>
          {renderSimplePercentRow(
            'Skill Bonus',
            buildAttributeTooltip('Skill Bonus'),
            skillBonus
          )}
          {renderSimplePercentRow(
            'Skill Training Bonus',
            buildAttributeTooltip('Skill Training Bonus'),
            skillTrainingBonus
          )}
        </Dl>
      </div>
    );
  };

  const renderEnemyStateReductionSection = () => {
    const reductions = [
      {
        displayLabel: 'Strength Reduction',
        attributeLabel: 'Strength',
        value: Number(affix.str_reduction ?? 0),
      },
      {
        displayLabel: 'Dexterity Reduction',
        attributeLabel: 'Dexterity',
        value: Number(affix.dex_reduction ?? 0),
      },
      {
        displayLabel: 'Intelligence Reduction',
        attributeLabel: 'Intelligence',
        value: Number(affix.int_reduction ?? 0),
      },
      {
        displayLabel: 'Charisma Reduction',
        attributeLabel: 'Charisma',
        value: Number(affix.chr_reduction ?? 0),
      },
      {
        displayLabel: 'Agility Reduction',
        attributeLabel: 'Agility',
        value: Number(affix.agi_reduction ?? 0),
      },
      {
        displayLabel: 'Durability Reduction',
        attributeLabel: 'Durability',
        value: Number(affix.dur_reduction ?? 0),
      },
      {
        displayLabel: 'Focus Reduction',
        attributeLabel: 'Focus',
        value: Number(affix.focus_reduction ?? 0),
      },
      {
        displayLabel: 'Skill Reduction',
        attributeLabel: 'Skill',
        value: Number(affix.skill_reduction ?? 0),
      },
    ];

    const activeReductions = reductions.filter(({ value }) => value > 0);

    if (activeReductions.length === 0) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Enemy State Reduction
        </h4>
        <p className="text-xs mb-2 text-gray-700 dark:text-gray-300">
          These stack additively and affect the enemy’s stats when initiating a
          fight.
        </p>
        <Dl>
          {activeReductions.map(({ displayLabel, attributeLabel, value }) =>
            renderNegativePercentRow(displayLabel, attributeLabel, value)
          )}
        </Dl>
      </div>
    );
  };

  const renderResistanceReductionSection = () => {
    const resistanceReduction = Number(affix.resistance_reduction ?? 0);

    if (resistanceReduction <= 0) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Resistance Reduction
        </h4>
        <Dl>
          <>
            <Dt>
              <span className={`inline-flex items-center gap-2 ${LABEL_WIDTH}`}>
                <StatInfoToolTip
                  label="Reduces the enemy's resistances to your attack. Stacks additively across all items."
                  value={resistanceReduction}
                  renderAsPercent
                  align="right"
                  custom_message
                />
                <span className="min-w-0 break-words">
                  Resistance Reduction
                </span>
              </span>
            </Dt>
            <Dd>
              <span className="inline-flex items-center gap-2 whitespace-nowrap">
                <i
                  className="fas fa-chevron-down text-rose-600"
                  aria-hidden="true"
                />
                <span className="font-semibold text-rose-600">
                  -{formatPercent(resistanceReduction)}
                </span>
              </span>
            </Dd>
          </>
        </Dl>
      </div>
    );
  };

  const renderMiscSection = () => {
    const lifeStealAmount = Number(affix.steal_life_amount ?? 0);
    const devouringLight = Number(affix.devouring_light ?? 0);
    const entrancedChance = Number(affix.entranced_chance ?? 0);

    if (lifeStealAmount <= 0 && devouringLight <= 0 && entrancedChance <= 0) {
      return null;
    }

    return (
      <div>
        <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Misc. Modifiers
        </h4>
        <Dl>
          {renderSimpleNumberRow(
            'Life Stealing',
            `Life Stealing: Steals ${formatFloat(
              lifeStealAmount
            )} life from the enemy during your attack phase. Non-vampire classes can steal up to 50% of the enemy's health, while Vampires can steal up to 99%. This stacks additively with other affixes.`,
            lifeStealAmount
          )}
          {renderSimplePercentRow(
            'Devouring Light',
            "Devouring Light: Stacks additively and reduces the enemy's chance to void you, which would render you weak and vulnerable. (If an enemy voids you, all enchantments on your gear fail.)",
            devouringLight
          )}
          {renderSimplePercentRow(
            'Entrancing',
            'Entrancing: Stacks additively and increases the chance to entrance an enemy, preventing them from attacking you, ambushing, or countering you. This does not prevent their ability to void or devoid you.',
            entrancedChance
          )}
        </Dl>
      </div>
    );
  };

  const renderWithSeparator = (
    section: React.ReactNode,
    isLastSection?: boolean
  ) => {
    if (!section) {
      return null;
    }

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
          {renderWithSeparator(renderSkillModifiersSection())}
          {renderWithSeparator(renderEnemyStateReductionSection())}
          {renderWithSeparator(renderResistanceReductionSection())}
          {renderWithSeparator(renderMiscSection(), true)}
        </div>
      </div>
    </>
  );
};

export default AttachedAffixDetails;
