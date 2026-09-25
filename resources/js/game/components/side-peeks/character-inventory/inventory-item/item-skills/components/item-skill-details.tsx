import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { Fragment, ReactNode } from 'react';

import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import SignedAdjustment from '../../../../../../reusable-components/item/partials/signed-adjustment';
import ItemSkillTreeState from '../enums/item-skill-tree-state';
import ItemSkillDetailsProps from '../types/item-skill-details-props';
import ItemSkillModifierRow from '../types/item-skill-modifier-row';
import {
  buildItemSkillModifierRows,
  describeCurrentModifier,
  describePerLevelModifier,
} from '../utils/build-item-skill-modifier-rows';

import { formatSignedPercent } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

export const buildItemSkillFooterOptions = (
  props: ItemSkillDetailsProps
): SidePeekOptionDefinition[] => {
  if (!props.can_manage_item_skills) {
    return [];
  }

  if (props.state === ItemSkillTreeState.TRAINING) {
    return [
      {
        id: 'stop-training-item-skill',
        label: 'Stop Training Skill',
        variant: ButtonVariant.DANGER,
        on_click: props.on_stop,
        disabled: props.processing,
        loading: props.processing,
      },
    ];
  }

  if (props.state !== ItemSkillTreeState.AVAILABLE) {
    return [];
  }

  return [
    {
      id: 'train-item-skill',
      label: 'Train Skill',
      variant: ButtonVariant.SUCCESS,
      on_click: props.on_train,
      disabled: props.processing,
      loading: props.processing,
    },
  ];
};

const ItemSkillDetails = (props: ItemSkillDetailsProps): ReactNode => {
  const modifierRows = buildItemSkillModifierRows(
    props.progression,
    props.skill
  );

  const renderStateAlert = (): ReactNode => {
    if (props.state === ItemSkillTreeState.LOCKED) {
      return (
        <Alert variant={AlertVariant.WARNING}>
          This skill is locked and cannot be trained until you meet the
          requirements below.
        </Alert>
      );
    }

    if (props.state === ItemSkillTreeState.MAXED) {
      return <Alert variant={AlertVariant.SUCCESS}>This skill is maxed.</Alert>;
    }

    if (props.state === ItemSkillTreeState.TRAINING) {
      return (
        <Alert variant={AlertVariant.INFO}>
          This is the currently-training skill.
        </Alert>
      );
    }

    return null;
  };

  const renderErrorMessage = (): ReactNode => {
    if (!props.error_message) {
      return null;
    }

    return <ApiErrorAlert apiError={props.error_message} />;
  };

  const renderSuccessMessage = (): ReactNode => {
    if (!props.success_message) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>{props.success_message}</Alert>
    );
  };

  const renderParentSkillName = (parent: ItemSkillDefinition): ReactNode => {
    if (!props.on_open_parent) {
      return parent.name;
    }

    return (
      <button
        type="button"
        onClick={props.on_open_parent}
        aria-label={`View parent skill ${parent.name}`}
        className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 rounded font-semibold underline focus:ring-2 focus:outline-none"
      >
        {parent.name}
      </button>
    );
  };

  const renderRequirements = (): ReactNode => {
    if (!props.parent) {
      return null;
    }

    return (
      <section>
        <h3 className="font-bold">Requirements</h3>
        <p>Parent Skill Name: {renderParentSkillName(props.parent)}</p>
        <p>Required Level: {props.skill.parent_level_needed ?? 'None'}</p>
      </section>
    );
  };

  const renderKillProgress = (): ReactNode => {
    if (props.state === ItemSkillTreeState.MAXED) {
      return <p>You have maxed this skill</p>;
    }

    return (
      <p>
        Kill Count: {props.progression.current_kill}/
        {props.skill.total_kills_needed}
      </p>
    );
  };

  const renderModifierRow = (row: ItemSkillModifierRow): ReactNode => (
    <Fragment key={row.label}>
      <dt>{row.label}</dt>
      <dd className="flex flex-wrap items-center justify-end gap-2">
        <SignedAdjustment
          value={row.current}
          display_text={formatSignedPercent(row.current)}
          screen_reader_text={describeCurrentModifier(row.label, row.current)}
        />
        <SignedAdjustment
          value={row.per_level}
          display_text={`(${formatSignedPercent(row.per_level)}/Lv)`}
          screen_reader_text={describePerLevelModifier(
            row.label,
            row.per_level
          )}
        />
      </dd>
    </Fragment>
  );

  const renderModifiers = (): ReactNode => {
    if (modifierRows.length === 0) {
      return <p className="mt-2">No modifiers for this skill.</p>;
    }

    return (
      <dl className="mt-2 grid grid-cols-2 gap-x-4 gap-y-2">
        {modifierRows.map(renderModifierRow)}
      </dl>
    );
  };

  return (
    <div className="space-y-4 px-4 text-gray-800 dark:text-gray-200">
      <div>
        <h2 className="text-xl font-bold">{props.skill.name}</h2>
        <p className="mt-2">{props.skill.description}</p>
        <a
          href="/information/ancestral-items"
          target="_blank"
          rel="noopener noreferrer"
          className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 mt-2 inline-block font-semibold underline focus:ring-2 focus:outline-none"
        >
          Ancestral Items help docs
          <span className="sr-only"> (opens in a new tab)</span>
        </a>
      </div>
      {renderStateAlert()}
      {renderErrorMessage()}
      {renderSuccessMessage()}
      {renderRequirements()}
      <section>
        <h3 className="font-bold">Progression</h3>
        <p>
          Level: {props.progression.current_level}/{props.skill.max_level}
        </p>
        {renderKillProgress()}
      </section>
      <section>
        <h3 className="font-bold">Modifiers</h3>
        {renderModifiers()}
      </section>
    </div>
  );
};

export default ItemSkillDetails;
