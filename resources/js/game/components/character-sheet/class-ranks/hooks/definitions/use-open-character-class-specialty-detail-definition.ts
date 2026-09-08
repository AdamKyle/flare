export default interface UseOpenCharacterClassSpecialtyDetailDefinition {
  openCharacterClassSpecialtyDetail: (
    characterId: number,
    gameClassSpecialId: number,
    specialtyName: string
  ) => void;
}
