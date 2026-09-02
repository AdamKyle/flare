import NpcQuestDefinition from '../../api/definitions/npc-quest-definition';

export default interface NpcQuestRelationshipCardProps {
  quest: NpcQuestDefinition;
  on_open_quest?: (quest_id: number) => void;
  on_open_item: (item_id: number, item_name: string) => void;
}
