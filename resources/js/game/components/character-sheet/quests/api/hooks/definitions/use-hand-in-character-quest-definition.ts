import CharacterQuestHandInResponseDefinition from '../../definitions/character-quest-hand-in-response-definition';

export default interface UseHandInCharacterQuestDefinition {
  handingIn: boolean;
  error: string | null;
  successMessage: string | null;
  handInQuest: () => Promise<CharacterQuestHandInResponseDefinition | null>;
}
