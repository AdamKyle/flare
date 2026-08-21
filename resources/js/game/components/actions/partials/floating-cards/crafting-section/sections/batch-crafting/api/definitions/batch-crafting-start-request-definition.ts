import CraftAmountRequestDefinition from './craft-amount-request-definition';
import CraftEventRequestDefinition from './craft-event-request-definition';
import CraftExperienceRequestDefinition from './craft-experience-request-definition';
import CraftSetRequestDefinition from './craft-set-request-definition';

type BatchCraftingStartRequestDefinition =
  | CraftAmountRequestDefinition
  | CraftExperienceRequestDefinition
  | CraftSetRequestDefinition
  | CraftEventRequestDefinition;

export default BatchCraftingStartRequestDefinition;
