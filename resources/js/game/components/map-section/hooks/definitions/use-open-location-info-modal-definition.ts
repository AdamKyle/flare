export default interface UseOpenCharacterKingdomInfoModalDefinition {
  openLocationDetails: (
    location_id: number,
    location_name: string,
    character_x: number,
    character_y: number
  ) => void;
}
