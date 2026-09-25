import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import axios from 'axios';

export const resolveApiErrorMessage = (
  requestError: unknown,
  fallbackMessage: string
): string => {
  if (!axios.isAxiosError<AxiosErrorDefinition>(requestError)) {
    return fallbackMessage;
  }

  const responseMessage = requestError.response?.data?.message;

  if (typeof responseMessage !== 'string') {
    return fallbackMessage;
  }

  return responseMessage;
};
