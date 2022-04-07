export const getCraftingType = (type: string): string => {
    if (['body', 'shield', 'leggings', 'feet', 'sleeves', 'helmet', 'gloves'].includes(type)) {
        return 'armour';
    }

    return type;
}
