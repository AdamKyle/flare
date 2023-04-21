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
            return 'character/'+characterId+'/inventory/smiths-workbench'
        default:
            return '';
    }
}

/**
 * Fetch the url for posting crafting.
 *
 * @param type
 * @param characterId
 */
export const craftingPostEndPoints = (type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null, characterId: number): string => {

    if (type === null) {
        return '';
    }

    switch(type) {
        case 'craft':
            return 'craft/' + characterId;
        case 'enchant':
            return 'enchant/' + characterId;
        case 'alchemy':
            return 'transmute/' + characterId;
        case 'trinketry':
            return 'trinket-craft/' + characterId;
        case 'workbench':
            return 'character/'+characterId+'/smithy-workbench/apply';
        default:
            return '';
    }
}
