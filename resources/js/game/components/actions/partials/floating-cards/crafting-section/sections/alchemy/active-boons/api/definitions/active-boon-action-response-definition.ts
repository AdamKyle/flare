import ActiveBoonDefinition from './active-boon-definition';

export default interface ActiveBoonActionResponseDefinition {
  message: string;
  boons: ActiveBoonDefinition[];
}
