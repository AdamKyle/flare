import QueenAffixDefinition from './queen-affix-definition';

export default interface QueenInventoryItemDefinition {
  id: number;
  affix_name: string;
  type: string;
  item_prefix_id: number | null;
  item_suffix_id: number | null;
  item_prefix: QueenAffixDefinition | null;
  item_suffix: QueenAffixDefinition | null;
}
