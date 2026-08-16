import { CraftingTypes } from '../enums/crafting-types';
import ScreenRegistryDefinition from './definitions/screen-registry-definition';
import AlchemySection from '../sections/alchemy/alchemy-section';
import BatchCraftingSection from '../sections/batch-crafting/batch-crafting-section';
import CraftingSection from '../sections/crafting/crafting-section';
import EnchantingSection from '../sections/enchanting/enchanting-section';
import GemCraftingSection from '../sections/gem-crafting/gem-crafting-section';
import LabyrinthOracleSection from '../sections/labyrinth-oracle/labyrinth-oracle-section';
import MenuSection from '../sections/menu/menu-section';
import QueenOfHeartsSection from '../sections/queen-of-hearts/queen-of-hearts-section';
import SeerCampSection from '../sections/seer-camp/seer-camp-section';
import TrinketrySection from '../sections/trinketry/trinketry-section';
import WorkBenchSection from '../sections/work-bench/work-bench-section';

export const ScreenMapper: ScreenRegistryDefinition['screens'] = {
  [CraftingTypes.HOME]: MenuSection,
  [CraftingTypes.BATCH_CRAFTING]: BatchCraftingSection,
  [CraftingTypes.CRAFT]: CraftingSection,
  [CraftingTypes.ENCHANT]: EnchantingSection,
  [CraftingTypes.ALCHEMY]: AlchemySection,
  [CraftingTypes.TRINKETS]: TrinketrySection,
  [CraftingTypes.GEM_CRAFTING]: GemCraftingSection,
  [CraftingTypes.QUEEN_OF_HEARTS]: QueenOfHeartsSection,
  [CraftingTypes.SEER_CAMP]: SeerCampSection,
  [CraftingTypes.WORK_BENCH]: WorkBenchSection,
  [CraftingTypes.LABYRINTH_ORACLE]: LabyrinthOracleSection,
};
