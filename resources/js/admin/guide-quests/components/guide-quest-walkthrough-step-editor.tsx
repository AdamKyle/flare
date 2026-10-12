import React, { ReactNode } from 'react';

import GuideQuestWalkthroughStepEditorProps from './types/guide-quest-walkthrough-step-editor-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import CheckboxField from 'ui/forms/checkbox-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const GuideQuestWalkthroughStepEditor = ({
  step,
  index,
  step_type_options: stepTypeOptions,
  target_options: targetOptions,
  event_options: eventOptions,
  is_first: isFirst,
  is_last: isLast,
  on_update: onUpdate,
  on_move: onMove,
  on_remove: onRemove,
}: GuideQuestWalkthroughStepEditorProps): ReactNode => {
  const option = (
    options: GuideQuestWalkthroughStepEditorProps['target_options'],
    value: string | null
  ) => options.find((item) => item.value === value);

  return (
    <fieldset className="space-y-3 rounded-sm border border-gray-300 p-4 dark:border-gray-700">
      <legend className="px-2 font-semibold">Step {index + 1}</legend>
      <div>
        <label htmlFor={`walkthrough-title-${index}`} className="mb-1 block">
          Title
        </label>
        <Input
          id={`walkthrough-title-${index}`}
          value={step.title}
          on_change={(title) => onUpdate({ title })}
          required
        />
      </div>
      <TextAreaField
        id={`walkthrough-content-${index}`}
        label="Content"
        value={step.content}
        on_change={(content) => onUpdate({ content })}
        required
        rows={4}
      />
      <div>
        <label id={`walkthrough-type-${index}`} className="mb-1 block">
          Step type
        </label>
        <Dropdown
          items={stepTypeOptions}
          pre_selected_item={option(stepTypeOptions, step.step_type)}
          aria_labelled_by={`walkthrough-type-${index}`}
          on_select={(item) => onUpdate({ step_type: String(item.value) })}
        />
      </div>
      <div>
        <label id={`walkthrough-target-${index}`} className="mb-1 block">
          Target
        </label>
        <Dropdown
          items={targetOptions}
          pre_selected_item={option(targetOptions, step.target)}
          selection_placeholder="No target"
          aria_labelled_by={`walkthrough-target-${index}`}
          on_select={(item) => onUpdate({ target: String(item.value) })}
          on_clear={() => onUpdate({ target: null })}
        />
      </div>
      <div>
        <label id={`walkthrough-event-${index}`} className="mb-1 block">
          Required event
        </label>
        <Dropdown
          items={eventOptions}
          pre_selected_item={option(eventOptions, step.required_event)}
          selection_placeholder="No event"
          aria_labelled_by={`walkthrough-event-${index}`}
          on_select={(item) => onUpdate({ required_event: String(item.value) })}
          on_clear={() => onUpdate({ required_event: null })}
        />
      </div>
      <CheckboxField
        id={`walkthrough-optional-${index}`}
        label="Skip this optional step when its target does not appear"
        checked={step.optional_if_target_missing}
        on_change={(optional_if_target_missing) =>
          onUpdate({ optional_if_target_missing })
        }
      />
      <div className="flex flex-wrap gap-2">
        <Button
          label="Move up"
          disabled={isFirst}
          on_click={() => onMove(-1)}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          label="Move down"
          disabled={isLast}
          on_click={() => onMove(1)}
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          label="Remove step"
          on_click={onRemove}
          variant={ButtonVariant.DANGER}
        />
      </div>
    </fieldset>
  );
};

export default GuideQuestWalkthroughStepEditor;
