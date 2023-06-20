export abstract class EquipPositions {

    public static getAllowedPositions(type: string) {
        switch(type) {
            case 'stave':
            case 'hammer':
            case 'bow':
            case 'weapon':
            case 'shield':
                return ['left-hand','right-hand'];
            case 'ring':
                return ['ring-one','ring-two'];
            case 'spell-damage':
            case 'spell-healing':
                return ['spell-one','spell-two'];
            case 'trinket':
                return ['trinket-one','trinket-two'];
            case 'armour':
            case 'artifact':
            default:
                return null;
        }
    }

    public static isTwoHanded(type: string): boolean {
        return ['bow', 'stave', 'hammer'].includes(type);
    }

    public static isArmour(type: string): boolean {
        return ['body', 'leggings', 'feet', 'sleeves', 'helmet', 'gloves'].includes(type);
    }

    public static isArtifact(type: string): boolean {
        return type === 'artifact'
    }
}
