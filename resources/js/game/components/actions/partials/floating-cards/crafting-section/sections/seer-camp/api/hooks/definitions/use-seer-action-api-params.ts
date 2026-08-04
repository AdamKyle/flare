import AddGemToItemRequestDefinition from '../../definitions/add-gem-to-item-request-definition';
import RemoveGemFromItemRequestDefinition from '../../definitions/remove-gem-from-item-request-definition';
import ReplaceGemOnItemRequestDefinition from '../../definitions/replace-gem-on-item-request-definition';
import RollItemSocketsRequestDefinition from '../../definitions/roll-item-sockets-request-definition';
import { SeerCampApiUrls } from '../../enums/seer-camp-api-urls';
export type SeerActionRequest =
  | AddGemToItemRequestDefinition
  | RemoveGemFromItemRequestDefinition
  | ReplaceGemOnItemRequestDefinition
  | RollItemSocketsRequestDefinition;
export default interface UseSeerActionApiParams {
  characterId: number;
  url: SeerCampApiUrls;
  request: SeerActionRequest | null;
}
