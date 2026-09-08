import CharacterQuestReadinessDefinition from '../../api/definitions/character-quest-readiness-definition';

export default interface CharacterQuestHandInActionsProps {
  is_completed: boolean;
  readiness: CharacterQuestReadinessDefinition;
  handing_in: boolean;
  success_message: string | null;
  error_message: string | null;
  on_hand_in: () => void;
}
