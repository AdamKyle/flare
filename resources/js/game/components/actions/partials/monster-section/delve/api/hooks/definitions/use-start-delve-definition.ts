import StartDelveRequestDefinition from '../../definitions/start-delve-request-definition';

export default interface UseStartDelveDefinition {
  starting: boolean;
  error: string | null;
  start: (request: StartDelveRequestDefinition) => Promise<boolean>;
}
