import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface SeerItemDefinition {
  slot_id: number;
  name: string;
  current_sockets: number;
  possible_socket_minimum: number;
  possible_socket_maximum: number;
  preview: CraftingItemPreviewDefinition;
}
