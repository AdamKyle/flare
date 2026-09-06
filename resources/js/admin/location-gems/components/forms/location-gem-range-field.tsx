import React, { ReactNode } from 'react';

import LocationGemRangeFieldProps from './types/location-gem-range-field-props';

import FieldWrapper from 'ui/forms/field-wrapper';
import Input from 'ui/input/input';

const LocationGemRangeField = ({
  id,
  label,
  value,
  error,
  on_change: onChange,
}: LocationGemRangeFieldProps): ReactNode => {
  return (
    <FieldWrapper id={id} label={label} error={error}>
      {(describedBy) => (
        <Input
          id={id}
          value={value}
          on_change={onChange}
          described_by={describedBy}
          invalid={!!error}
          place_holder="e.g. 1-6"
        />
      )}
    </FieldWrapper>
  );
};

export default LocationGemRangeField;
