export default interface UseOpenTeleportModalDefinition {
  openTeleport: (
    character_id: number,
    character_x: number,
    character_y: number
  ) => void;
}
