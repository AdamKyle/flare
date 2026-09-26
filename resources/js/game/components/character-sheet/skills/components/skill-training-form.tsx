import React, { ReactNode, useId, useState } from 'react';

import SkillTrainingFormProps from './types/skill-training-form-props';
import { skillXpSacrificeOptions } from '../constants/skill-xp-sacrifice-options';
import { formatSkillPercentage } from '../utils/format-skill-percentage';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const SkillTrainingForm = ({
  skill,
  submitting,
  error,
  success_message: successMessage,
  on_train: onTrain,
  on_cancel: onCancel,
}: SkillTrainingFormProps): ReactNode => {
  const [xpPercentage, setXpPercentage] = useState<number | null>(null);

  const labelId = useId();
  const helpId = useId();

  const handleSelect = (item: DropdownItem) => {
    setXpPercentage(Number(item.value));
  };

  const handleTrain = () => {
    if (xpPercentage === null) {
      return;
    }

    onTrain(xpPercentage);
  };

  const renderFeedback = (): ReactNode => {
    if (error !== null) {
      return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
    }

    if (successMessage !== null) {
      return <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>;
    }

    return null;
  };

  const renderTrainingControls = (): ReactNode => {
    if (skill.is_training) {
      return (
        <div className="flex flex-col gap-3">
          <p className="text-sm text-gray-700 dark:text-gray-300">
            You are sacrificing {formatSkillPercentage(skill.xp_towards)} of the
            XP you earn to train this Skill.
          </p>
          <Button
            label="Cancel Training"
            variant={ButtonVariant.DANGER}
            additional_css="w-full"
            disabled={submitting}
            on_click={onCancel}
          />
        </div>
      );
    }

    return (
      <div className="flex flex-col gap-3">
        <label
          id={labelId}
          className="text-sm font-medium text-gray-800 dark:text-gray-200"
        >
          How much XP do you want to sacrifice?
        </label>
        <Dropdown
          aria_labelled_by={labelId}
          aria_described_by={helpId}
          items={skillXpSacrificeOptions}
          on_select={handleSelect}
          selection_placeholder="Select an XP percentage"
          disabled={submitting}
        />
        <p id={helpId} className="text-xs text-gray-600 dark:text-gray-400">
          This percentage of the XP you earn goes to this Skill instead of your
          Character. Only one Skill can be trained at a time.
        </p>
        <Button
          label="Train Skill"
          variant={ButtonVariant.SUCCESS}
          additional_css="w-full"
          disabled={submitting || xpPercentage === null}
          on_click={handleTrain}
        />
      </div>
    );
  };

  return (
    <section aria-label="Skill training" className="flex flex-col gap-3">
      <h3 className="font-semibold text-gray-900 dark:text-gray-100">
        Training
      </h3>
      {renderFeedback()}
      {renderTrainingControls()}
    </section>
  );
};

export default SkillTrainingForm;
