import React, { useEffect, useState } from 'react';

import { GuideQuestWalkthroughStepDefinition } from '../api/definitions/guide-quest-definition';
import GuideQuestResponseDefinition from '../api/definitions/guide-quest-response-defintion';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

interface ManageGuideQuestWalkthroughProps {
  data: GuideQuestResponseDefinition;
  on_update: (steps: GuideQuestWalkthroughStepDefinition[]) => void;
}

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
        <fieldset
          key={step.id ?? `new-step-${index}`}
          className="space-y-3 rounded-sm border border-gray-300 p-4 dark:border-gray-700"
        >
          <legend className="px-2 font-semibold">Step {index + 1}</legend>
          <label className="block">
            <span className="mb-1 block">Title</span>
            <input
              value={step.title}
              onChange={(event) =>
                updateStep(index, { title: event.target.value })
              }
              className="w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            />
          </label>
          <label className="block">
            <span className="mb-1 block">Content</span>
            <textarea
              value={step.content}
              onChange={(event) =>
                updateStep(index, { content: event.target.value })
              }
              className="min-h-24 w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            />
          </label>
          <label className="block">
            <span className="mb-1 block">Step type</span>
            <select
              value={step.step_type}
              onChange={(event) =>
                updateStep(index, { step_type: event.target.value })
              }
              className="w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            >
              {data.walkthrough_step_type_options.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
          <label className="block">
            <span className="mb-1 block">Target</span>
            <select
              value={step.target ?? ''}
              onChange={(event) =>
                updateStep(index, { target: event.target.value || null })
              }
              className="w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            >
              <option value="">No target</option>
              {data.walkthrough_target_options.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
          <label className="block">
            <span className="mb-1 block">Required event</span>
            <select
              value={step.required_event ?? ''}
              onChange={(event) =>
                updateStep(index, {
                  required_event: event.target.value || null,
                })
              }
              className="w-full rounded-sm border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
            >
              <option value="">No event</option>
              {data.walkthrough_event_options.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={step.optional_if_target_missing}
              onChange={(event) =>
                updateStep(index, {
                  optional_if_target_missing: event.target.checked,
                })
              }
            />
            Skip this optional step when its target does not appear
          </label>
          <div className="flex flex-wrap gap-2">
            <Button
              label="Move up"
              disabled={index === 0}
              on_click={() => moveStep(index, -1)}
              variant={ButtonVariant.PRIMARY}
            />
            <Button
              label="Move down"
              disabled={index === steps.length - 1}
              on_click={() => moveStep(index, 1)}
              variant={ButtonVariant.PRIMARY}
            />
            <Button
              label="Remove step"
              on_click={() => removeStep(index)}
              variant={ButtonVariant.DANGER}
            />
          </div>
        </fieldset>
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
