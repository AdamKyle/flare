import React from 'react';

import {
  InventoryPositionDefinition,
  Position,
} from './enums/equipment-positions';
import {
  nonArmourEquippableItems,
  InventoryItemTypes,
} from './enums/inventory-item-types';
import EquippedSlot from './equipped-slot';
import EquippedSlotsProps from './types/equipped-slots-props';
import { fetchEquippedItemForSlot } from './utils/fetch-equipped-item-for-slot';

const EquippedSlots = ({
  equipped_items,
  on_open_item_details,
}: EquippedSlotsProps) => {
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
            on_open_item_details={on_open_item_details}
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
            on_open_item_details={on_open_item_details}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.BODY,
              InventoryPositionDefinition.BODY
            )}
            positionName={'Body'}
            position={Position.BODY}
            on_open_item_details={on_open_item_details}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.SLEEVES,
              InventoryPositionDefinition.SLEEVES
            )}
            positionName={'Sleeves (Right)'}
            position={Position.SLEEVES_RIGHT}
            on_open_item_details={on_open_item_details}
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
            on_open_item_details={on_open_item_details}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.LEGGINGS,
              InventoryPositionDefinition.LEGGINGS
            )}
            positionName={'Leggings'}
            position={Position.LEGGINGS}
            on_open_item_details={on_open_item_details}
          />
          <EquippedSlot
            equipped_item={fetchEquippedItemForSlot(
              equipped_items,
              InventoryItemTypes.GLOVES,
              InventoryPositionDefinition.GLOVES
            )}
            positionName={'Gloves (Right)'}
            position={Position.GLOVES_RIGHT}
            on_open_item_details={on_open_item_details}
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
            on_open_item_details={on_open_item_details}
          />
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4 sm:grid-cols-2">
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.LEFT_HAND
          )}
          positionName={'Weapon (Left Hand)'}
          position={Position.LEFT_HAND}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RIGHT_HAND
          )}
          positionName={'Weapon (Right Hand)'}
          position={Position.RING_HAND}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RING_TWO
          )}
          positionName={'Ring (Ring Two)'}
          position={Position.RING_TWO}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.RING_ONE
          )}
          positionName={'Ring (Ring One)'}
          position={Position.RING_ONE}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.SPELL_ONE
          )}
          positionName={'Spell (Spell One)'}
          position={Position.SPELL_ONE}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.SPELL_TWO
          )}
          positionName={'Spell (Spell Two)'}
          position={Position.SPELL_TWO}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            nonArmourEquippableItems,
            InventoryPositionDefinition.TRINKET
          )}
          positionName={'Trinket'}
          position={Position.TRINKET}
          on_open_item_details={on_open_item_details}
        />
        <EquippedSlot
          equipped_item={fetchEquippedItemForSlot(
            equipped_items,
            InventoryItemTypes.ARTIFACT,
            InventoryPositionDefinition.ARTIFACT
          )}
          positionName={'Artifact'}
          position={Position.ARTIFACT}
          on_open_item_details={on_open_item_details}
        />
      </div>
    </div>
  );
};

export default EquippedSlots;
