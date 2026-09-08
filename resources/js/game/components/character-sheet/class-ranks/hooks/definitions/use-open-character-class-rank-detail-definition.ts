export default interface UseOpenCharacterClassRankDetailDefinition {
  openCharacterClassRankDetail: (
    characterId: number,
    gameClassId: number,
    className: string
  ) => void;
}
