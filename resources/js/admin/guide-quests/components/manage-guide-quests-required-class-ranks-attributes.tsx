import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredClassRanksAttributes = ({
  state,
  errors,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <NumberField
      id="guide-quest-required-class-specials-equipped"
      label="Required Total Class Specialities Equipped"
      value={state.required_class_specials_equipped}
      on_change={(value) => onChange('required_class_specials_equipped', value)}
      error={errors.required_class_specials_equipped}
    />
    <NumberField
      id="guide-quest-required-class-rank-level"
      label="Required Class Rank level"
      value={state.required_class_rank_level}
      on_change={(value) => onChange('required_class_rank_level', value)}
      error={errors.required_class_rank_level}
    />
  </div>
);

export default ManageGuideQuestsRequiredClassRanksAttributes;
