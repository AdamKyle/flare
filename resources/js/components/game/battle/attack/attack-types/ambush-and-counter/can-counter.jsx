import {random} from "lodash";

export const canCounter = (character, defender) => {
  const counterChance = character.counter_chance - defender.counter_resistance;

  if (counterChance <= 0.0) {
    return false;
  }

  const roll          = random(0, 100);

  return (roll + roll * counterChance) > 99;
}

export const canEnemyCounter = (attacker, defender) => {
  return true;
  const counterChance = attacker.counter_chance - defender.counter_resistance;

  if (counterChance <= 0.0) {
    return false;
  }

  const roll          = random(0, 100);

  return (roll + roll * counterChance) > 99;
}

export const canCounterAgain = () => {
  return true;
  const roll = random(1, 100);

  return (roll + roll * 0.02) > 99;
}
