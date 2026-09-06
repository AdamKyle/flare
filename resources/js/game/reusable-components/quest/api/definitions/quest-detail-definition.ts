import QuestDependencyDefinition from './quest-dependency-definition';
import QuestIdentityDefinition from './quest-identity-definition';
import QuestItemFactualDefinition, {
  GameMapIdentityDefinition,
  NpcIdentityDefinition,
} from '../../../quest-item/types/quest-item-factual-definition';
import { QuestKind } from '../../enums/quest-kind';

export interface QuestGiverNpcDefinition {
  id: number;
  name: string;
  type: number;
  x_position: number;
  y_position: number;
  must_be_at_same_location: boolean;
  game_map: GameMapIdentityDefinition | null;
}

export interface QuestStoryDefinition {
  before_completion_markdown: string | null;
  after_completion_markdown: string | null;
}

export interface QuestStructureDefinition {
  parent_quest: QuestDependencyDefinition | null;
  child_quests: QuestDependencyDefinition[];
  required_quest: QuestDependencyDefinition | null;
  required_quest_chain: QuestDependencyDefinition[];
  compatibility_parent_chain_quest_id: number | null;
}

export interface QuestAvailabilityDefinition {
  raid: QuestIdentityDefinition | null;
  only_for_event: number | null;
}

export interface QuestFactionRequirementDefinition {
  game_map: GameMapIdentityDefinition;
  required_level: number | null;
}

export interface QuestFactionLoyaltyRequirementDefinition {
  npc: NpcIdentityDefinition;
  required_fame_level: number | null;
}

/**
 * The canonical factual Quest Item payload plus its identity, as returned
 * whenever a Quest requirement or reward embeds a quest Item.
 */
export interface QuestRelatedItemDefinition extends QuestItemFactualDefinition {
  item_id: number;
}

export interface QuestRequirementsDefinition {
  primary_item: QuestRelatedItemDefinition | null;
  secondary_item: QuestRelatedItemDefinition | null;
  reincarnated_times: number | null;
  access_to_map: GameMapIdentityDefinition | null;
  faction: QuestFactionRequirementDefinition | null;
  faction_loyalty: QuestFactionLoyaltyRequirementDefinition | null;
  currencies: {
    gold: number | null;
    gold_dust: number | null;
    shards: number | null;
    copper_coins: number | null;
  };
}

export interface QuestUnlockedSkillDefinition {
  id: number;
  name: string;
  type: number;
}

export interface QuestRewardsDefinition {
  item: QuestRelatedItemDefinition | null;
  gold: number | null;
  gold_dust: number | null;
  shards: number | null;
  xp: number | null;
  skill: QuestUnlockedSkillDefinition | null;
  feature: number | null;
  passive: QuestIdentityDefinition | null;
}

export default interface QuestDetailDefinition {
  id: number;
  name: string;
  kind: QuestKind;
  story: QuestStoryDefinition;
  npc: QuestGiverNpcDefinition | null;
  structure: QuestStructureDefinition;
  availability: QuestAvailabilityDefinition;
  requirements: QuestRequirementsDefinition;
  rewards: QuestRewardsDefinition;
}
