import AlchemyAmountRequestDefinition from './alchemy-amount-request-definition';
import AlchemyExperienceRequestDefinition from './alchemy-experience-request-definition';
import CraftAmountRequestDefinition from './craft-amount-request-definition';
import CraftAndEnchantAmountRequestDefinition from './craft-and-enchant-amount-request-definition';
import CraftAndEnchantExperienceRequestDefinition from './craft-and-enchant-experience-request-definition';
import CraftAndEnchantSetRequestDefinition from './craft-and-enchant-set-request-definition';
import CraftEventRequestDefinition from './craft-event-request-definition';
import CraftExperienceRequestDefinition from './craft-experience-request-definition';
import CraftSetRequestDefinition from './craft-set-request-definition';
import EnchantEventRequestDefinition from './enchant-event-request-definition';
import HolyOilsSelectedItemsRequestDefinition from './holy-oils-selected-items-request-definition';
import HolyOilsSetRequestDefinition from './holy-oils-set-request-definition';
import TrinketryRequestDefinition from './trinketry-request-definition';

type BatchCraftingStartRequestDefinition =
  | CraftAmountRequestDefinition
  | CraftExperienceRequestDefinition
  | CraftSetRequestDefinition
  | CraftEventRequestDefinition
  | CraftAndEnchantAmountRequestDefinition
  | CraftAndEnchantExperienceRequestDefinition
  | CraftAndEnchantSetRequestDefinition
  | EnchantEventRequestDefinition
  | AlchemyAmountRequestDefinition
  | AlchemyExperienceRequestDefinition
  | HolyOilsSelectedItemsRequestDefinition
  | HolyOilsSetRequestDefinition
  | TrinketryRequestDefinition;

export default BatchCraftingStartRequestDefinition;
