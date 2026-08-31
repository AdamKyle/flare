import NpcDetailDefinition from '../../api/definitions/npc-detail-definition';
import UseNpcQuestsDefinition from '../../api/hooks/definitions/use-npc-quests-definition';
import UseNpcRewardItemsDefinition from '../../api/hooks/definitions/use-npc-reward-items-definition';

export default interface NpcDetailBodyProps {
  npc: NpcDetailDefinition;
  quests: UseNpcQuestsDefinition;
  reward_items: UseNpcRewardItemsDefinition;
  on_open_item: (item_id: number, item_name: string) => void;
  on_open_quest?: (quest_id: number) => void;
  on_open_map?: (id: number) => void;
}
