import {random} from "lodash";

export default class Voidance {

  canPlayerDevoidEnemy(devoidChance) {

    return true;

    if (devoidChance >= 1) {
      return true;
    }

    return  random(1, 100) > (100 - 100 * devoidChance);
  }

  canVoidEnemy(voidChance) {

    if (voidChance >= 1) {
      return true;
    }

    return  random(1, 100) > (100 - 100 * voidChance);
  }
}