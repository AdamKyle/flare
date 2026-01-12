import React from 'react';

import {
  InventoryPositionDefinition,
  Position,
} from './enums/equipment-positions';
import {
  handBasedItems,
  InventoryItemTypes,
} from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import EquippedSlotsProps from './types/equipped-slots-props';
import { fetchEquippedItemForSlot } from './utils/fetch-equipped-armour';

const EquippedSlots = ({ equipped_items }: EquippedSlotsProps) => {
  return (
    <div className="flex w-full flex-col items-center gap-4 sm:flex-row md:justify-center lg:w-3/4 lg:p-4">
      <div className="flex flex-col items-center space-y-4">
        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.HELMET,
              InventoryPositionDefinition.HELMET
            )}
            positionName={'Helmet'}
            position={Position.HELMET}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES,
              InventoryPositionDefinition.SLEEVES
            )}
            positionName={'Sleeves (Left)'}
            position={Position.SLEEVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.BODY,
              InventoryPositionDefinition.BODY
            )}
            positionName={'Body'}
            position={Position.BODY}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES,
              InventoryPositionDefinition.SLEEVES
            )}
            positionName={'Sleeves (Right)'}
            position={Position.SLEEVES_RIGHT}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES,
              InventoryPositionDefinition.GLOVES
            )}
            positionName={'Gloves (Left)'}
            position={Position.GLOVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.LEGGINGS,
              InventoryPositionDefinition.LEGGINGS
            )}
            positionName={'Leggings'}
            position={Position.LEGGINGS}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES,
              InventoryPositionDefinition.GLOVES
            )}
            positionName={'Gloves (Right)'}
            position={Position.GLOVES_RIGHT}
          />
        </div>

        <div>
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.FEET,
              InventoryPositionDefinition.FEET
            )}
            positionName={'Feet'}
            position={Position.FEET}
          />
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4 sm:grid-cols-2">
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.LEFT_HAND
          )}
          positionName={'Weapon (Left Hand)'}
          position={Position.LEFT_HAND}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.RIGHT_HAND
          )}
          positionName={'Weapon (Right Hand)'}
          position={Position.RING_HAND}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.RING_TWO
          )}
          positionName={'Ring (Ring One)'}
          position={Position.RING_TWO}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.RING_ONE
          )}
          positionName={'Ring (Ring One)'}
          position={Position.RING_ONE}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.SPELL_TWO
          )}
          positionName={'Spell (Spell One)'}
          position={Position.SPELL_TWO}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.SPELL_TWO
          )}
          positionName={'Spell (Spell Two)'}
          position={Position.SPELL_ONE}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            handBasedItems,
            InventoryPositionDefinition.TRINKET
          )}
          positionName={'Trinket'}
          position={Position.TRINKET}
        />
      </div>
    </div>
  );
};

export default EquippedSlots;
