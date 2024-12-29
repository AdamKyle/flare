import React, { ReactNode } from 'react';

import { Position } from './enums/equipment-positions';
import EquippedSlot from './equipped-slot';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const InventorySection = (): ReactNode => {
  return (
    <div className="relative">
      <div className="absolute left-0 top-4 flex flex-col items-start space-y-4">
        <IconButton
          label="Backpack"
          icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
          variant={ButtonVariant.PRIMARY}
          on_click={() => {}}
          additional_css="w-full"
        />
        <IconButton
          label="Usable"
          icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
          variant={ButtonVariant.PRIMARY}
          on_click={() => {}}
          additional_css="w-full"
        />
        <IconButton
          label="Gem Bag"
          icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
          variant={ButtonVariant.PRIMARY}
          on_click={() => {}}
          additional_css="w-full"
        />
        <IconButton
          label="Sets"
          icon={<i className="ra ra-player text-sm" aria-hidden="true"></i>}
          variant={ButtonVariant.PRIMARY}
          on_click={() => {}}
          additional_css="w-full"
        />
      </div>

      {/* Main content area */}
      <div className="flex justify-center">
        <div className="flex items-center p-4 space-x-4 w-3/4 justify-center">
          <div className="flex flex-col items-center space-y-4">
            <div>
              <EquippedSlot
                positionName={'Helmet'}
                position={Position.HELMET}
              />
            </div>

            <div className="grid grid-cols-3 gap-4">
              <EquippedSlot
                positionName={'Sleeves (Left)'}
                position={Position.SLEEVES_LEFT}
              />
              <EquippedSlot positionName={'Body'} position={Position.BODY} />
              <EquippedSlot
                positionName={'Sleeves (Right)'}
                position={Position.SLEEVES_RIGHT}
              />
            </div>

            <div className="grid grid-cols-3 gap-4">
              <EquippedSlot
                positionName={'Gloves (Left)'}
                position={Position.GLOVES_LEFT}
              />
              <EquippedSlot
                positionName={'Leggings'}
                position={Position.LEGGINGS}
              />
              <EquippedSlot
                positionName={'Gloves (Right)'}
                position={Position.GLOVES_RIGHT}
              />
            </div>

            <div>
              <EquippedSlot positionName={'Feet'} position={Position.FEET} />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <EquippedSlot
              positionName={'Weapon (Left Hand)'}
              position={Position.WEAPON_LEFT}
            />
            <EquippedSlot
              positionName={'Weapon (Right Hand)'}
              position={Position.WEAPON_RIGHT}
            />
            <EquippedSlot
              positionName={'Ring (Ring One)'}
              position={Position.RING_LEFT}
            />
            <EquippedSlot
              positionName={'Ring (Ring One)'}
              position={Position.RING_RIGHT}
            />
            <EquippedSlot
              positionName={'Spell (Spell One)'}
              position={Position.SPELL_LEFT}
            />
            <EquippedSlot
              positionName={'Spell (Spell Two)'}
              position={Position.SPELL_RIGHT}
            />
            <EquippedSlot
              positionName={'Trinket'}
              position={Position.TRINKET}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default InventorySection;
