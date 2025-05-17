import CharacterMapPosition from '../../types/character-map-position';

export default interface UseEmitCharacterPosition {
  characterPosition: CharacterMapPosition;
  emitCharacterPosition: (characterPosition: CharacterMapPosition) => void;
}
