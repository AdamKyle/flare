import { GemProgressionGlobalDefinition } from './gem-progression-status-definition';

export default interface GemProfileProgressionUpdateDefinition {
  profileType: string;
  profileId: number;
  globalProgress: GemProgressionGlobalDefinition;
}
