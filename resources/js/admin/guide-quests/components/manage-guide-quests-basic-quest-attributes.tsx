import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';
import {
  guideQuestDropdownItems,
  selectedGuideQuestOption,
} from '../utils/guide-quest-form-options';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Dropdown from 'ui/drop-down/drop-down';
import FieldWrapper from 'ui/forms/field-wrapper';
import NumberField from 'ui/forms/number-field';
import TextAreaField from 'ui/forms/text-area-field';
import Input from 'ui/input/input';

const ManageGuideQuestsBasicQuestAttributes = ({
  state,
  errors,
  form_options: formOptions,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => {
  const eventItems = formOptions.events.map((label, value) => ({
    value,
    label,
  }));
  const parentItems = guideQuestDropdownItems(formOptions.guide_quests ?? {});

  return (
    <div className="space-y-4">
      <FieldWrapper
        id="guide-quest-name"
        label="Guide Quest Name"
        required
        error={errors.name}
      >
        {(describedBy) => (
          <Input
            id="guide-quest-name"
            value={state.name}
            on_change={(value) => onChange('name', value)}
            described_by={describedBy}
            invalid={!!errors.name}
            required
          />
        )}
      </FieldWrapper>
      <TextAreaField
        id="guide-quest-information"
        label="General Information"
        value={state.instructions}
        on_change={(value) => onChange('instructions', value)}
        error={errors.instructions}
        rows={8}
      />
      <Alert variant={AlertVariant.INFO}>
        Special Guide Quests can replace the current Guide Quest during
        configured events or progression conditions.
      </Alert>
      <FieldWrapper
        id="guide-quest-event"
        label="Only During Event"
        error={errors.only_during_event}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-event"
            items={eventItems}
            pre_selected_item={selectedGuideQuestOption(
              eventItems,
              state.only_during_event
            )}
            selection_placeholder="No event"
            on_select={(item) =>
              onChange('only_during_event', Number(item.value))
            }
            on_clear={() => onChange('only_during_event', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.only_during_event}
          />
        )}
      </FieldWrapper>
      <FieldWrapper
        id="guide-quest-parent"
        label="Belongs to Parent Guide Quest"
        error={errors.parent_id}
      >
        {(describedBy) => (
          <Dropdown
            id="guide-quest-parent"
            items={parentItems}
            pre_selected_item={selectedGuideQuestOption(
              parentItems,
              state.parent_id
            )}
            selection_placeholder="No parent"
            on_select={(item) => onChange('parent_id', Number(item.value))}
            on_clear={() => onChange('parent_id', null)}
            aria_described_by={describedBy}
            aria_invalid={!!errors.parent_id}
          />
        )}
      </FieldWrapper>
      <NumberField
        id="guide-quest-unlock-level"
        label="Only When the character reaches level"
        value={state.unlock_at_level}
        on_change={(value) => onChange('unlock_at_level', value)}
        error={errors.unlock_at_level}
      />
    </div>
  );
};

export default ManageGuideQuestsBasicQuestAttributes;
