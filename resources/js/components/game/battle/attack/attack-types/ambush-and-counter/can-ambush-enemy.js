import {random} from "lodash";

export const canAmbush = (attackType, defender) => {
  const ambushChance = attackType.ambush_chance - defender.ambush_resistance;

  if (ambushChance <= 0.0) {
    return false;
  }

  const roll         = random(1, 100);

  return (roll + roll * ambushChance) > 99;
}

export const canEnemyAmbush = (attacker, defender) => {
  const ambushChance = attacker.ambush_chance - defender.ambush_resistance;

  if (ambushChance <= 0.0) {
    return false;
  }

  const roll         = random(1, 100);

  return (roll + roll * ambushChance) > 99;

}
