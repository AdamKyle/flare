import React, { ReactNode } from 'react';

import FieldWrapperProps from 'ui/forms/types/field-wrapper-props';

const FieldWrapper = ({
  id,
  label,
  required,
  description,
  error,
  children,
}: FieldWrapperProps): ReactNode => {
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
        className="mt-1 text-xs text-gray-600 dark:text-gray-400"
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
        className="mt-1 text-xs text-rose-600 dark:text-rose-400"
      >
        {error}
      </p>
    );
  };

  return (
    <div className="mb-4">
      <label
        htmlFor={id}
        className="mb-1 block text-sm font-medium text-gray-800 dark:text-gray-200"
      >
        {label}
        {required && (
          <span aria-hidden="true" className="text-rose-600 dark:text-rose-400">
            {' '}
            *
          </span>
        )}
      </label>
      {renderDescription()}
      {children(describedBy)}
      {renderError()}
    </div>
  );
};

export default FieldWrapper;
