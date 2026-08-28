import React, { ReactNode } from 'react';

import FieldWrapper from 'ui/forms/field-wrapper';
import TextAreaFieldProps from 'ui/forms/types/text-area-field-props';

const TextAreaField = ({
  id,
  label,
  value,
  on_change,
  required,
  description,
  error,
  disabled,
  rows,
  placeholder,
}: TextAreaFieldProps): ReactNode => {
  return (
    <FieldWrapper
      id={id}
      label={label}
      required={required}
      description={description}
      error={error}
    >
      {(describedBy) => (
        <textarea
          id={id}
          value={value}
          rows={rows ?? 4}
          placeholder={placeholder}
          disabled={disabled}
          required={required}
          aria-describedby={describedBy}
          aria-invalid={!!error}
          onChange={(event) => on_change(event.target.value)}
          className="focus:ring-danube-500 w-full rounded-sm border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />
      )}
    </FieldWrapper>
  );
};

export default TextAreaField;
