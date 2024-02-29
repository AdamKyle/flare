export default interface ItemAtonement {
    atonements: {
        Fire: number;
        Ice: number;
        Water: number;
    };
    elemental_damage: {
        name: string;
        amount: number;
    };
}
