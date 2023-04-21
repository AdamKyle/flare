import {random} from "lodash";

export default class Voidance {

  canPlayerDevoidEnemy(devoidChance) {


    if (devoidChance >= 1) {
      return true;
    }

    if (devoidChance <= 0.0) {
      return false;
    }

    return  random(1, 100) > (100 - 100 * devoidChance);
  }

  canVoidEnemy(voidChance) {

    if (voidChance >= 1) {
      return true;
    }

    if (voidChance <= 0.0) {
      return false;
    }

    return  random(1, 100) > (100 - 100 * voidChance);
  }
}