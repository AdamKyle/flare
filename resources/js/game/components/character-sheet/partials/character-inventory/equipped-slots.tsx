import React from "react";

import {CharacterEquippedApiUrls} from "./api/enums/character-equipped-api-urls";
import useCharacterEquippedItemsApi from "./api/hooks/use-character-equipped-items-api";
import {Position} from "./enums/equipment-positions";
import {InventoryItemTypes} from "./enums/inventory-item-types";
import EquippedSlot from "./equipped-slot";
import EquippedSlotsProps from "./types/equipped-slots-props";
import {fetchEquippedArmour} from "./utils/fetch-equipped-armour";

import {GameDataError} from "game-data/components/game-data-error";

import InfiniteLoader from "ui/loading-bar/infinite-loader";



const EquippedSlots = ({character_id}: EquippedSlotsProps) => {
  const {data, loading, error} = useCharacterEquippedItemsApi({
    url: CharacterEquippedApiUrls.CHARACTER_EQUIPPED,
    urlParams: {
      character: character_id,
    }
  });

  if (error) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  return (
    <div className="flex items-center lg:p-4 space-x-4 w-full lg:w-3/4 md:justify-center">
      <div className="flex flex-col items-center space-y-4">
        <div>
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.HELMET
            )}
            positionName={'Helmet'}
            position={Position.HELMET}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.SLEEVES
            )}
            positionName={'Sleeves (Left)'}
            position={Position.SLEEVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.BODY
            )}
            positionName={'Body'}
            position={Position.BODY}
          />
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.SLEEVES
            )}
            positionName={'Sleeves (Right)'}
            position={Position.SLEEVES_RIGHT}
          />
        </div>

        <div className="grid grid-cols-3 gap-4">
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.GLOVES
            )}
            positionName={'Gloves (Left)'}
            position={Position.GLOVES_LEFT}
          />
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.LEGGINGS
            )}
            positionName={'Leggings'}
            position={Position.LEGGINGS}
          />
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.GLOVES
            )}
            positionName={'Gloves (Right)'}
            position={Position.GLOVES_RIGHT}
          />
        </div>

        <div>
          <EquippedSlot
            equipped_item={fetchEquippedArmour(
              data,
              InventoryItemTypes.FEET
            )}
            positionName={'Feet'}
            position={Position.FEET}
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Weapon (Left Hand)'}
          position={Position.WEAPON_LEFT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Weapon (Right Hand)'}
          position={Position.WEAPON_RIGHT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Ring (Ring One)'}
          position={Position.RING_LEFT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Ring (Ring One)'}
          position={Position.RING_RIGHT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Spell (Spell One)'}
          position={Position.SPELL_LEFT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Spell (Spell Two)'}
          position={Position.SPELL_RIGHT}
        />
        <EquippedSlot
          equipped_item={undefined}
          positionName={'Trinket'}
          position={Position.TRINKET}
        />
      </div>
    </div>
  )
}

export default EquippedSlots;