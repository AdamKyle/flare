import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import axios from 'axios';

export const extractBatchCraftingApiError = (
  error: unknown,
  fallbackMessage: string
): string => {
  if (axios.isAxiosError<AxiosErrorDefinition>(error)) {
    return error.response?.data?.message ?? fallbackMessage;
  }

  return fallbackMessage;
};
