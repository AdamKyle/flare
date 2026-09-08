import { LocationFormField } from '../enums/location-form-field';
import LocationFormErrors from '../types/location-form-errors';
import LocationFormState from '../types/location-form-state';

const VALIDATABLE_LOCATION_FORM_FIELDS: readonly string[] = [
  LocationFormField.Name,
  LocationFormField.Description,
  LocationFormField.X,
  LocationFormField.Y,
  LocationFormField.Type,
  LocationFormField.PinCssClass,
  LocationFormField.RequiredQuestItemId,
  LocationFormField.QuestRewardItemId,
  LocationFormField.HoursToDrop,
  LocationFormField.MinutesBetweenDelveFights,
];

export const isLocationValidatableField = (
  field: keyof LocationFormState
): field is keyof LocationFormErrors & keyof LocationFormState =>
  VALIDATABLE_LOCATION_FORM_FIELDS.includes(field);
