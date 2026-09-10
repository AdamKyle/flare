import React, { Fragment, ReactNode } from 'react';

import ClassPrerequisiteCard from './class-prerequisite-card';
import { coreStatLabel } from '../enums/core-stat';
import ClassDetailProps from '../types/class-detail-props';

import { formatPercent } from 'game-utils/format-number';

import Card from 'ui/cards/card';
import DetailGrid from 'ui/detail-grid/detail-grid';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

interface LabeledValueRow {
  label: string;
  value: number;
}

const ClassDetail = ({
  game_class: gameClass,
  on_open_class: onOpenClass,
  single_column: singleColumn,
}: ClassDetailProps): ReactNode => {
  const renderRows = (rows: LabeledValueRow[]): ReactNode =>
    rows.map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{row.value}</Dd>
      </Fragment>
    ));

  const renderPercentRows = (rows: LabeledValueRow[]): ReactNode =>
    rows.map((row) => (
      <Fragment key={row.label}>
        <Dt>{row.label}</Dt>
        <Dd>{formatPercent(row.value)}</Dd>
      </Fragment>
    ));

  const attributeRows: LabeledValueRow[] = [
    { label: 'Strength', value: gameClass.attributes.str_mod },
    { label: 'Durability', value: gameClass.attributes.dur_mod },
    { label: 'Dexterity', value: gameClass.attributes.dex_mod },
    { label: 'Charisma', value: gameClass.attributes.chr_mod },
    { label: 'Intelligence', value: gameClass.attributes.int_mod },
    { label: 'Agility', value: gameClass.attributes.agi_mod },
    { label: 'Focus', value: gameClass.attributes.focus_mod },
  ].filter((row) => row.value > 0);

  const combatModifierRows: LabeledValueRow[] = [
    { label: 'Accuracy', value: gameClass.combat_modifiers.accuracy_mod },
    { label: 'Dodge', value: gameClass.combat_modifiers.dodge_mod },
    { label: 'Defense', value: gameClass.combat_modifiers.defense_mod },
    { label: 'Looting', value: gameClass.combat_modifiers.looting_mod },
  ].filter((row) => row.value > 0);

  const hasAttributes = attributeRows.length > 0;
  const hasCombatModifiers = combatModifierRows.length > 0;
  const hasUnlockRequirements = Boolean(gameClass.unlock_requirements);
  const hasSecondRow = hasCombatModifiers || hasUnlockRequirements;

  return (
    <DetailGrid single_column={singleColumn}>
      {gameClass.description && (
        <div className="col-span-full">
          <Card>
            <p className="text-glacier-800 dark:text-glacier-200">
              {gameClass.description}
            </p>
          </Card>
        </div>
      )}

      <Card>
        <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
          Basic
        </h2>
        <Dl>
          <Dt>Damage Stat</Dt>
          <Dd>{coreStatLabel(gameClass.damage_stat)}</Dd>
          <Dt>To Hit Stat</Dt>
          <Dd>{coreStatLabel(gameClass.to_hit_stat)}</Dd>
        </Dl>
      </Card>

      {hasAttributes && (
        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Attributes
          </h2>
          <Dl>{renderRows(attributeRows)}</Dl>
        </Card>
      )}

      {hasSecondRow && <Separator additional_css="col-span-full my-1" />}

      {hasCombatModifiers && (
        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Combat Modifiers
          </h2>
          <Dl>{renderPercentRows(combatModifierRows)}</Dl>
        </Card>
      )}

      {gameClass.unlock_requirements && (
        <Card>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-4 text-lg font-semibold">
            Unlock Requirements
          </h2>
          <div className="flex flex-col gap-2">
            <ClassPrerequisiteCard
              id={gameClass.unlock_requirements.primary_required_class.id}
              name={gameClass.unlock_requirements.primary_required_class.name}
              required_level={
                gameClass.unlock_requirements.primary_required_class_level
              }
              on_click={onOpenClass}
            />
            <ClassPrerequisiteCard
              id={gameClass.unlock_requirements.secondary_required_class.id}
              name={gameClass.unlock_requirements.secondary_required_class.name}
              required_level={
                gameClass.unlock_requirements.secondary_required_class_level
              }
              on_click={onOpenClass}
            />
          </div>
        </Card>
      )}
    </DetailGrid>
  );
};

export default ClassDetail;
