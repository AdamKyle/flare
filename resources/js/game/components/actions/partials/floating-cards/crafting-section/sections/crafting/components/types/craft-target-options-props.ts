import type { ChangeEvent } from 'react';

export default interface CraftTargetOptionsProps {
  canCraftForNpc: boolean;
  canCraftForEvent: boolean;
  craftForNpc: boolean;
  craftForEvent: boolean;
  onCraftForNpcChange: (event: ChangeEvent<HTMLInputElement>) => void;
  onCraftForEventChange: (event: ChangeEvent<HTMLInputElement>) => void;
}
