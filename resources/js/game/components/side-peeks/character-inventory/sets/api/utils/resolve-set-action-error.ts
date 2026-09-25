import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { AxiosError } from 'axios';

export const resolveSetActionError = (
  requestError: unknown,
  fallbackMessage: string
): AxiosErrorDefinition => {
  if (!(requestError instanceof AxiosError)) {
    return { message: fallbackMessage };
  }

  const responseMessage = requestError.response?.data?.message;

  if (typeof responseMessage !== 'string') {
    return { message: fallbackMessage };
  }

  return { message: responseMessage };
};
