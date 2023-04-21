import ExtraActionType from "./extra-action-type";
import {random} from "lodash";

export default class Healing {

  constructor() {
    this.battleMessages = [];
  }

  extraHealing(character, characterCurrentHealth) {
    if (character.extra_action_chance.class_name === character.class) {
      const extraActionChance = character.extra_action_chance;

      if (!this.canUse(extraActionChance.chance)) {
        return characterCurrentHealth;
      }

      if (extraActionChance.type === ExtraActionType.PROPHET_HEALING && extraActionChance.has_item) {
        this.battleMessages.push({
          message: 'The Lord\'s blessing washes over you. Your healing spells fire again!',
        });

        characterCurrentHealth += character.heal_for;

        this.battleMessages.push({
          message: 'The Lord\'s blessing heals you for: ' + character.heal_for.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        });
      }
    }

    return characterCurrentHealth;
  }

  canUse(extraActionChance) {
    const dc = Math.round(100 - (100 * extraActionChance));

    return random(1, 100) > dc;
  }

  getMessages() {
    return this.battleMessages;
  }
}
