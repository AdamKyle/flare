export default interface OriginalAtonement {
    atonements: Atonements[];
    elemental_damage: ElementalDamage;
}

export interface Atonements {
    name: string;
    total: number;
}

export interface ElementalDamage {
    name: string;
    amount: number;
}
