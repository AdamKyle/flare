import FactionDefinition from '../../definitions/faction-definition';

export default interface UseFetchFactionsDefinition {
  factions: FactionDefinition[];
  loading: boolean;
  error: string | null;
}
