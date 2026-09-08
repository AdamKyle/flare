import { LocationFormStep } from '../enums/location-form-step';
import LocationFormErrors from '../types/location-form-errors';

interface LocationFieldDescriptor {
  step: LocationFormStep;
  id: string;
}

const LOCATION_FIELD_ORDER: Array<{
  key: keyof LocationFormErrors;
  step: LocationFormStep;
  id: string;
}> = [
  { key: 'name', step: LocationFormStep.Basic, id: 'location-name' },
  {
    key: 'description',
    step: LocationFormStep.Basic,
    id: 'location-description',
  },
  { key: 'x', step: LocationFormStep.Basic, id: 'location-x' },
  { key: 'y', step: LocationFormStep.Basic, id: 'location-y' },
  { key: 'type', step: LocationFormStep.Rules, id: 'location-type' },
  {
    key: 'pin_css_class',
    step: LocationFormStep.Rules,
    id: 'location-pin',
  },
  {
    key: 'required_quest_item_id',
    step: LocationFormStep.Rules,
    id: 'location-required-quest-item',
  },
  {
    key: 'quest_reward_item_id',
    step: LocationFormStep.Rules,
    id: 'location-quest-reward-item',
  },
  {
    key: 'hours_to_drop',
    step: LocationFormStep.Rules,
    id: 'location-hours-to-drop',
  },
  {
    key: 'minutes_between_delve_fights',
    step: LocationFormStep.Rules,
    id: 'location-minutes-between-delve-fights',
  },
];

export const resolveFirstInvalidLocationField = (
  errors: LocationFormErrors
): LocationFieldDescriptor | null => {
  const match = LOCATION_FIELD_ORDER.find((field) =>
    Boolean(errors[field.key])
  );

  return match ? { step: match.step, id: match.id } : null;
};
