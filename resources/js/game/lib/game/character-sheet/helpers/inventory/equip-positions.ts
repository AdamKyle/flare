export abstract class EquipPositions {

    public static getAllowedPositions(type: string) {
        switch(type) {
            case 'stave':
            case 'hammer':
            case 'bow':
            case 'weapon':
                return ['left-hand','right-hand'];
            case 'rings':
                return ['ring-one','ring-two'];
            case 'spells':
                return ['spell-one','spell-two'];
            case 'artifact':
                return ['artifact-one','artifact-two'];
            case 'trinket':
                return ['artifact-one','artifact-two'];
            case 'armour':
            default:
                return null;
        }
    }

    public static isTwoHanded(type: string) {
        return ['bow', 'stave', 'hammer'].includes(type);
    }

    public static isArmour(type: string) {
        return ['body', 'shield', 'leggings', 'feet', 'sleeves', 'helmet', 'gloves'].includes(type);
    }
}
