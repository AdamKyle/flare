export default interface ErrorChannelShapeDefinition<TError> {
  listen<TPayload>(event: string, cb: (payload: TPayload) => void): void;
  stopListening<TPayload>(event: string, cb: (payload: TPayload) => void): void;
  unsubscribe(): void;
  leave(): void;
  error?(callback: (status: TError) => void): void;
}
