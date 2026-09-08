import React, { Fragment, ReactNode } from 'react';

import { attackTypeLabel } from '../enums/attack-type';
import ClassMasteryDetailProps from '../types/class-mastery-detail-props';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

interface LabeledValueRow {
  label: string;
  value: number | null;
}

interface ClassMasterySection {
  key: string;
  content: ReactNode;
}

const ClassMasteryDetail = ({
  class_mastery: classMastery,
}: ClassMasteryDetailProps): ReactNode => {
  const meaningfulRows = (rows: LabeledValueRow[]): LabeledValueRow[] =>
    rows.filter((row) => row.value !== null && row.value > 0);

  const renderNumberRows = (rows: LabeledValueRow[]): ReactNode =>
    meaningfulRows(rows).map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{row.value}</Dd>
      </Fragment>
    ));

  const renderPercentRows = (rows: LabeledValueRow[]): ReactNode =>
    meaningfulRows(rows).map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{formatPercent(row.value ?? 0)}</Dd>
      </Fragment>
    ));

  const renderBaseFactsSection = (): ReactNode => (
    <section className="space-y-3">
      <Dl>
        <Dt>Class</Dt>
        <Dd>{classMastery.game_class.name}</Dd>
        <Dt>Type</Dt>
        <Dd>{classMastery.type === 'attack' ? 'Attack' : 'Passive'}</Dd>
        {classMastery.requires_class_rank_level > 0 && (
          <Fragment>
            <Dt>Requires Class Level</Dt>
            <Dd>{classMastery.requires_class_rank_level}</Dd>
          </Fragment>
        )}
      </Dl>
      {classMastery.description && (
        <p className="text-glacier-800 dark:text-glacier-200">
          {classMastery.description}
        </p>
      )}
    </section>
  );

  const renderAttackSection = (): ReactNode => {
    if (classMastery.type !== 'attack') {
      return null;
    }

    const numberRows: LabeledValueRow[] = [
      {
        label: 'Specialty Damage',
        value: classMastery.attack.specialty_damage,
      },
      {
        label: 'Increase Per Level',
        value: classMastery.attack.increase_specialty_damage_per_level,
      },
    ];
    const percentRows: LabeledValueRow[] = [
      {
        label: 'Specialty Damage Uses Damage Stat Amount',
        value: classMastery.attack.specialty_damage_uses_damage_stat_amount,
      },
    ];
    const attackTypeRequired = classMastery.attack.attack_type_required;

    if (
      meaningfulRows(numberRows).length === 0 &&
      meaningfulRows(percentRows).length === 0 &&
      !attackTypeRequired
    ) {
      return null;
    }

    return (
      <section className="space-y-3">
        <h2 className="text-glacier-900 dark:text-glacier-100 text-lg font-semibold">
          Attack
        </h2>
        <Dl>
          {renderNumberRows(numberRows)}
          {renderPercentRows(percentRows)}
          {attackTypeRequired && (
            <Fragment>
              <Dt>Attack Type Required</Dt>
              <Dd>{attackTypeLabel(attackTypeRequired)}</Dd>
            </Fragment>
          )}
        </Dl>
      </section>
    );
  };

  const modifierRows: LabeledValueRow[] = [
    {
      label: 'Base Damage Modifier',
      value: classMastery.modifiers.base_damage_mod,
    },
    { label: 'Base AC Modifier', value: classMastery.modifiers.base_ac_mod },
    {
      label: 'Base Healing Modifier',
      value: classMastery.modifiers.base_healing_mod,
    },
    {
      label: 'Base Spell Damage Modifier',
      value: classMastery.modifiers.base_spell_damage_mod,
    },
    { label: 'Health Modifier', value: classMastery.modifiers.health_mod },
    {
      label: 'Base Damage Stat Increase',
      value: classMastery.modifiers.base_damage_stat_increase,
    },
  ];

  const evasionRows: LabeledValueRow[] = [
    {
      label: 'Spell Evasion',
      value: classMastery.evasion_and_reductions.spell_evasion,
    },
    {
      label: 'Affix Damage Reduction',
      value: classMastery.evasion_and_reductions.affix_damage_reduction,
    },
    {
      label: 'Healing Reduction',
      value: classMastery.evasion_and_reductions.healing_reduction,
    },
    {
      label: 'Skill Reduction',
      value: classMastery.evasion_and_reductions.skill_reduction,
    },
    {
      label: 'Resistance Reduction',
      value: classMastery.evasion_and_reductions.resistance_reduction,
    },
  ];

  const renderModifiersSection = (): ReactNode => (
    <section className="space-y-3">
      <h2 className="text-glacier-900 dark:text-glacier-100 text-lg font-semibold">
        Modifiers
      </h2>
      <Dl>{renderPercentRows(modifierRows)}</Dl>
    </section>
  );

  const renderEvasionSection = (): ReactNode => (
    <section className="space-y-3">
      <h2 className="text-glacier-900 dark:text-glacier-100 text-lg font-semibold">
        Evasion and Reductions
      </h2>
      <Dl>{renderPercentRows(evasionRows)}</Dl>
    </section>
  );

  const sections: ClassMasterySection[] = [
    { key: 'base-facts', content: renderBaseFactsSection() },
  ];

  const attackSection = renderAttackSection();

  if (attackSection) {
    sections.push({ key: 'attack', content: attackSection });
  }

  if (meaningfulRows(modifierRows).length > 0) {
    sections.push({ key: 'modifiers', content: renderModifiersSection() });
  }

  if (meaningfulRows(evasionRows).length > 0) {
    sections.push({ key: 'evasion', content: renderEvasionSection() });
  }

  return (
    <div className="flex flex-col">
      {sections.map((section, index) => (
        <Fragment key={section.key}>
          {index > 0 && <Separator additional_css="my-4" />}
          {section.content}
        </Fragment>
      ))}
    </div>
  );
};

export default ClassMasteryDetail;
