import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseMarketMutationDefinition<TResponse> {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  run: (
    execute: (signal: AbortSignal) => Promise<TResponse>
  ) => Promise<TResponse | null>;
  clear_error: () => void;
}
