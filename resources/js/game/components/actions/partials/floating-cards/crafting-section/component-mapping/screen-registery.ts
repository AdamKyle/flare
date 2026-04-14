import { CraftingTypes } from '../enums/crafting-types';
import AlchemySection from '../screens/alchemy-section';
import MenuSection from '../screens/menu-section';
import ScreenRegistryDefinition from './definitions/screen-registry-definition';
import CraftingSection from '../screens/crafting-section';
import EnchantingSection from '../screens/enchanting-section';
import TrinketrySection from '../screens/trinketry-section';

export const ScreenMapper: ScreenRegistryDefinition['screens'] = {
  [CraftingTypes.HOME]: MenuSection,
  [CraftingTypes.CRAFT]: CraftingSection,
  [CraftingTypes.ENCHANT]: EnchantingSection,
  [CraftingTypes.TRINKETS]: TrinketrySection,
  [CraftingTypes.ALCHEMY]: AlchemySection,
};
