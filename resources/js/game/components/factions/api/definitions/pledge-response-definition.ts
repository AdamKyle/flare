import FactionDefinition from './faction-definition';

export default interface PledgeResponseDefinition {
  message: string;
  factions: FactionDefinition[];
}
