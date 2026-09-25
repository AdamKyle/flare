import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface MarketContextDefinition {
  character_id: number;
  realtime_version: number;
  is_listing_available: (listingId: number) => boolean;
  update_character: (character: Partial<CharacterSheetDefinition>) => void;
}
