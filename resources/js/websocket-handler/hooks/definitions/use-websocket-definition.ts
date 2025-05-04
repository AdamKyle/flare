import WebsocketErrorDefinition from './websocket-error-definition';

export interface UseWebsocketReturn<TError = unknown> {
  listen<TPayload>(
    eventName: string,
    handler: (payload: TPayload) => void
  ): () => void;
  error: WebsocketErrorDefinition<TError> | null;
}
