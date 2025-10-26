import { match } from 'ts-pattern';

import { LocationTypes } from '../enums/location-types';

/**
 * Get Special Location Name
 *
 * @param specialLocationType
 */
export const getLocationTypeName = (
  specialLocationType: LocationTypes
): string => {
  return match(specialLocationType)
    .with(LocationTypes.ALCHEMY_CHURCH, () => 'Alchemy Church')
    .with(LocationTypes.BROKEN_ANVIL, () => 'Hells Broken Anvil')
    .with(LocationTypes.PURGATORY_SMITH_HOUSE, () => 'Purgatory Smiths House')
    .with(LocationTypes.GOLD_MINES, () => 'Gold Mines')
    .with(LocationTypes.PURGATORY_DUNGEONS, () => 'Purgatory Dungeons')
    .with(LocationTypes.UNDERWATER_CAVES, () => 'Underwater Caves')
    .with(LocationTypes.TEAR_FABRIC_TIME, () => 'Tear in the fabric of time')
    .with(LocationTypes.THE_OLD_CHURCH, () => 'The Old Church')
    .with(LocationTypes.TWISTED_GATE, () => 'The Twisted Gate')
    .with(LocationTypes.LORDS_STRONG_HOLD, () => 'Lords Strong Hold')
    .with(
      LocationTypes.TWSITED_MAIDENS_DUNGEONS,
      () => 'Twisted Maidens Dungeons'
    )
    .with(LocationTypes.CAVE_OF_MEMORIES, () => 'Cave of Memories')
    .otherwise(() => 'Unknown.');
};
