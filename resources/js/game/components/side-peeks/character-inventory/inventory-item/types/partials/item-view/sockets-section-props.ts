import { SocketDefinition } from '../../../../../../../api-definitions/items/equippable-item-definitions/socket-definition';

export default interface SocketsSectionProps {
  total: number;
  sockets: SocketDefinition[];
}
