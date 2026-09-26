export default interface UseFactionPostRequestDefinition {
  submitting: boolean;
  error: string | null;
  clear_error: () => void;
  submit: <TResponse, TRequest extends object>(
    url: string,
    data: TRequest,
    fallbackMessage: string
  ) => Promise<TResponse | null>;
}
