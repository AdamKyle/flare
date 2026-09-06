/**
 * Truthful Quest Item ownership presentation contract. Absence of an
 * ownership state means no authoritative ownership statement is available
 * for this rendering context — it is never inferred from absence.
 */
enum QuestItemOwnershipState {
  HAS = 'has',
  HAD = 'had',
}

export default QuestItemOwnershipState;
