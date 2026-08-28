import React, { ReactNode } from 'react';

import CheckboxFieldProps from 'ui/forms/types/checkbox-field-props';

const CheckboxField = ({
  id,
  label,
  checked,
  on_change,
  description,
  error,
  disabled,
}: CheckboxFieldProps): ReactNode => {
  const descriptionId = description ? `${id}-description` : undefined;
  const errorId = error ? `${id}-error` : undefined;
  const describedBy =
    [descriptionId, errorId].filter(Boolean).join(' ') || undefined;

  const renderDescription = () => {
    if (!description) {
      return null;
    }

    return (
      <p
        id={descriptionId}
        className="mt-1 ml-6 text-xs text-gray-600 dark:text-gray-400"
      >
        {description}
      </p>
    );
  };

  const renderError = () => {
    if (!error) {
      return null;
    }

    return (
      <p
        id={errorId}
        role="alert"
        className="mt-1 ml-6 text-xs text-rose-600 dark:text-rose-400"
      >
        {error}
      </p>
    );
  };

  return (
    <div className="mb-4">
      <div className="flex items-center gap-2">
        <input
          id={id}
          type="checkbox"
          checked={checked}
          disabled={disabled}
          aria-describedby={describedBy}
          aria-invalid={!!error}
          onChange={(event) => on_change(event.target.checked)}
          className="text-danube-600 focus:ring-danube-500 h-4 w-4 rounded-sm border-gray-500 focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700"
        />
        <label
          htmlFor={id}
          className="text-sm font-medium text-gray-800 dark:text-gray-200"
        >
          {label}
        </label>
      </div>
      {renderDescription()}
      {renderError()}
    </div>
  );
};

export default CheckboxField;
