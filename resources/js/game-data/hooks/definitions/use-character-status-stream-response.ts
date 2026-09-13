interface CharacterStatusesDefinition {
  is_dead: boolean;
  can_attack: boolean;
  can_attack_again_at: number;
}

export default interface UseCharacterStatusStreamResponse {
  characterStatuses: CharacterStatusesDefinition;
}
