import React, { useEffect, useState } from 'react';

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
  data,
  on_update,
}: ManageGuideQuestWalkthroughProps) => {
  const [steps, setSteps] = useState<GuideQuestWalkthroughStepDefinition[]>(
    data.walkthrough_steps
  );

  useEffect(() => on_update(steps), [on_update, steps]);

  const updateStep = (
    index: number,
    changes: Partial<GuideQuestWalkthroughStepDefinition>
  ) => {
    setSteps((previous) =>
      previous.map((step, stepIndex) =>
        stepIndex === index ? { ...step, ...changes } : step
      )
    );
  };

  const removeStep = (index: number) => {
    setSteps((previous) =>
      previous
        .filter((unusedStep, stepIndex) => stepIndex !== index)
        .map((step, stepIndex) => ({ ...step, position: stepIndex + 1 }))
    );
  };

  const moveStep = (index: number, direction: -1 | 1) => {
    const destination = index + direction;

    if (destination < 0 || destination >= steps.length) {
      return;
    }

    setSteps((previous) => {
      const reordered = previous.slice();
      [reordered[index], reordered[destination]] = [
        reordered[destination],
        reordered[index],
      ];

      return reordered.map((step, stepIndex) => ({
        ...step,
        position: stepIndex + 1,
      }));
    });
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
          step_type_options={data.walkthrough_step_type_options}
          target_options={data.walkthrough_target_options}
          event_options={data.walkthrough_event_options}
          is_first={index === 0}
          is_last={index === steps.length - 1}
          on_update={(changes) => updateStep(index, changes)}
          on_move={(direction) => moveStep(index, direction)}
          on_remove={() => removeStep(index)}
        />
      ))}
      <Button
        label="Add walkthrough step"
        on_click={() =>
          setSteps((previous) => [...previous, emptyStep(previous.length + 1)])
        }
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  );
};

export default ManageGuideQuestWalkthrough;
