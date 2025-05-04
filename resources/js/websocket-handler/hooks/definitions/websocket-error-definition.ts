export default interface WebsocketErrorDefinition<TError = unknown> {
  type: 'connection' | 'subscription';
  info: TError;
}
