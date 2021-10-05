export default class CanEntranceEnemy {

  constructor() {
    this.battleMessages = [];
  }

  canEntranceEnemy(attackType, defender, type) {
    let canEntrance   = false;
    const chance      = attackType.affixes.entrancing_chance;

    if (type === 'player') {
      if (attackType.affixes.entrancing_chance > 0.0) {
        const cantResist     = attackType.affixes.cant_be_resisted;
        const canBeEntranced = random(1, 100) > (100 - (100 * chance));


        if (cantResist || canBeEntranced) {
          this.battleMessages.push({
            'message': 'The enemy is dazed by your enchantments!'
          });

          canEntrance = true;
        } else if (canBeEntranced) {
          const dc = 100 - (100 * defender.affix_resistance);

          if (dc <= 0 || random(0, 100) > dc) {
            this.battleMessages.push({
              'message': 'The enemy is resists your entrancing enchantments!'
            });

          } else {
            this.battleMessages.push({
              'message': 'The enemy is dazed by your enchantments!'
            });

            canEntrance = true;
          }
        } else {
          this.battleMessages.push({
            'message': 'The enemy is resists your entrancing enchantments!'
          });
        }
      }
    }

    return canEntrance;
  }

  getBattleMessages() {
    return this.battleMessages;
  }
}