import React, { ReactNode } from 'react';

import GuideQuestWalkthroughStepEditor from './guide-quest-walkthrough-step-editor';
import ManageGuideQuestWalkthroughProps from './types/manage-guide-quest-walkthrough-props';
import { GuideQuestWalkthroughStepDefinition } from '../api/definitions/guide-quest-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const emptyStep = (position: number): GuideQuestWalkthroughStepDefinition => ({
  position,
  title: '',
  content: '',
  target: null,
  step_type: 'continue',
  required_event: null,
  optional_if_target_missing: false,
});

const ManageGuideQuestWalkthrough = ({
  steps,
  form_options: formOptions,
  on_change: onChange,
}: ManageGuideQuestWalkthroughProps): ReactNode => {
  const updateStep = (
    index: number,
    changes: Partial<GuideQuestWalkthroughStepDefinition>
  ): void =>
    onChange(
      steps.map((step, stepIndex) =>
        stepIndex === index ? { ...step, ...changes } : step
      )
    );

  const removeStep = (index: number): void =>
    onChange(
      steps
        .filter((unusedStep, stepIndex) => stepIndex !== index)
        .map((step, stepIndex) => ({ ...step, position: stepIndex + 1 }))
    );

  const moveStep = (index: number, direction: -1 | 1): void => {
    const destination = index + direction;

    if (destination < 0 || destination >= steps.length) {
      return;
    }

    const reordered = steps.slice();
    [reordered[index], reordered[destination]] = [
      reordered[destination],
      reordered[index],
    ];
    onChange(
      reordered.map((step, stepIndex) => ({
        ...step,
        position: stepIndex + 1,
      }))
    );
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-gray-600 dark:text-gray-300">
        Walkthroughs are optional. Targets and success events are controlled by
        the application.
      </p>
      {steps.map((step, index) => (
        <GuideQuestWalkthroughStepEditor
          key={step.id ?? `new-step-${index}`}
          step={step}
          index={index}
          step_type_options={formOptions.walkthrough_step_type_options}
          target_options={formOptions.walkthrough_target_options}
          event_options={formOptions.walkthrough_event_options}
          is_first={index === 0}
          is_last={index === steps.length - 1}
          on_update={(changes) => updateStep(index, changes)}
          on_move={(direction) => moveStep(index, direction)}
          on_remove={() => removeStep(index)}
        />
      ))}
      <Button
        label="Add walkthrough step"
        on_click={() => onChange([...steps, emptyStep(steps.length + 1)])}
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default ManageGuideQuestWalkthrough;
