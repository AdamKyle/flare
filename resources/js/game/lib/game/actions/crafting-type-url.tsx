/**
 * Fetch the url for getting a list of craftable items.
 *
 * @param type
 * @param characterId
 */
export const craftingGetEndPoints = (type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null, characterId: number): string => {

    if (type === null) {
        return '';
    }

    switch(type) {
        case 'craft':
            return 'crafting/' + characterId;
        case 'enchant':
            return 'enchanting/' + characterId;
        case 'alchemy':
            return 'alchemy/' + characterId;
        case 'trinketry':
            return 'trinket-crafting/' + characterId;
        case 'workbench':
        default:
            return '';
    }
}
