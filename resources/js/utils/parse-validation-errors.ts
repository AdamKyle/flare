interface LaravelValidationErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}

/**
 * Extract a flat field-name to first-message map from a Laravel 422 validation
 * error response body. Returns an empty object when the shape does not match.
 */
export const parseValidationErrors = (
  data: unknown
): Record<string, string> => {
  if (!data || typeof data !== 'object') {
    return {};
  }

  const response = data as LaravelValidationErrorResponse;

  if (!response.errors) {
    return {};
  }

  const fieldErrors: Record<string, string> = {};

  for (const [field, messages] of Object.entries(response.errors)) {
    if (Array.isArray(messages) && messages.length > 0) {
      fieldErrors[field] = messages[0];
    }
  }

  return fieldErrors;
};
