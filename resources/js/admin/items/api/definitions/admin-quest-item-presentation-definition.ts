import QuestItemFactualDefinition from '../../../../game/reusable-components/quest-item/types/quest-item-factual-definition';

/**
 * Matches App\Game\Core\Items\Transformers\QuestItemTransformer::transform()
 * exactly, extending the shared permission-neutral `QuestItemFactualDefinition`
 * with the additional raw fields the transformer also returns that the
 * shared factual presentation itself never reads, but Admin/Location/NPC
 * relationship lists (`ReadOnlyItemCard`, `DataTable` rows) do: `item_id`
 * (opens the Item detail SidePeek), `can_drop`, `usable`, and `craft_only`.
 */
export default interface AdminQuestItemPresentationDefinition extends QuestItemFactualDefinition {
  item_id: number;
  can_drop: boolean;
  usable: boolean;
  craft_only: boolean;
}
