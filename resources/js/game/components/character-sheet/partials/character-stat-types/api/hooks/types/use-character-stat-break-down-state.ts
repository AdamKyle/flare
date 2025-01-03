import CharacterStatBreakDownDefinition from '../../../api-definitions/character-stat-break-down-definition';

export default interface UseCharacterStatBreakDownState {
  data: CharacterStatBreakDownDefinition | null;
  error: {
    message: string;
  } | null;
  loading: boolean;
}
