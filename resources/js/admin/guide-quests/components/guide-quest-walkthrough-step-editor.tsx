import React, { ReactNode } from 'react';

import GuideQuestWalkthroughStepEditorProps from './types/guide-quest-walkthrough-step-editor-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import CheckboxField from 'ui/forms/checkbox-field';
import FieldWrapper from 'ui/forms/field-wrapper';
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
      <FieldWrapper id={`walkthrough-title-${index}`} label="Title" required>
        {(describedBy) => (
          <Input
            id={`walkthrough-title-${index}`}
            value={step.title}
            on_change={(title) => onUpdate({ title })}
            described_by={describedBy}
            required
          />
        )}
      </FieldWrapper>
      <TextAreaField
        id={`walkthrough-content-${index}`}
        label="Content"
        value={step.content}
        on_change={(content) => onUpdate({ content })}
        required
        rows={4}
      />
      <FieldWrapper id={`walkthrough-type-${index}`} label="Step type" required>
        {(describedBy) => (
          <Dropdown
            id={`walkthrough-type-${index}`}
            items={stepTypeOptions}
            pre_selected_item={option(stepTypeOptions, step.step_type)}
            aria_described_by={describedBy}
            aria_required
            on_select={(item) => onUpdate({ step_type: String(item.value) })}
          />
        )}
      </FieldWrapper>
      <FieldWrapper id={`walkthrough-target-${index}`} label="Target">
        {(describedBy) => (
          <Dropdown
            id={`walkthrough-target-${index}`}
            items={targetOptions}
            pre_selected_item={option(targetOptions, step.target)}
            selection_placeholder="No target"
            aria_described_by={describedBy}
            on_select={(item) => onUpdate({ target: String(item.value) })}
            on_clear={() => onUpdate({ target: null })}
          />
        )}
      </FieldWrapper>
      <FieldWrapper id={`walkthrough-event-${index}`} label="Required event">
        {(describedBy) => (
          <Dropdown
            id={`walkthrough-event-${index}`}
            items={eventOptions}
            pre_selected_item={option(eventOptions, step.required_event)}
            selection_placeholder="No event"
            aria_described_by={describedBy}
            on_select={(item) =>
              onUpdate({ required_event: String(item.value) })
            }
            on_clear={() => onUpdate({ required_event: null })}
          />
        )}
      </FieldWrapper>
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
