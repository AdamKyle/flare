import React from 'react';

import ServerChatItemProps from './types/server-chat-item-props';
import InventoryItem from '../character-inventory/inventory-item/inventory-item';

const ServerChatItem = ({ slot_id, character_id }: ServerChatItemProps) => {
  return (
    <InventoryItem
      slot_id={slot_id}
      character_id={character_id}
      on_action={() => {}}
    />
  );
};

export default ServerChatItem;
