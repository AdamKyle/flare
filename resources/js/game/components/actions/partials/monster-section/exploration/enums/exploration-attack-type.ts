/**
 * Exploration's `attack_type` request value, validated backend-side against
 * `App\Game\Core\Combat\Values\AttackType` (underscored, non-voided cases
 * only) — distinct from the manual-combat `AttackType` enum's own values.
 */
export enum ExplorationAttackType {
  ATTACK = 'attack',
  CAST = 'cast',
  CAST_AND_ATTACK = 'cast_and_attack',
  ATTACK_AND_CAST = 'attack_and_cast',
  DEFEND = 'defend',
}
