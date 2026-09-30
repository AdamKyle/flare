import CharacterGemDefinition from '../../gems/character-gem-definition';

export interface SocketDefinition {
  id: number;
  position: number;
  gem: CharacterGemDefinition | null;
}
