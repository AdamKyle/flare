
export interface Atonement {
    Fire: number,
    Ice: number,
    Water: number,
}

export interface ElementalDamage {
    name: string;
    amount: number;
}

export interface InventoryAtonements {
    data: {
        atonements: Atonement,
        elemental_damage: ElementalDamage
    },
    item_name: string;
}

export default interface ItemAtonement {
    item_atonement: {
        atonements: Atonement,
        elemental_damage: ElementalDamage
    },
    inventory_atonements: InventoryAtonements[]|[]
}
