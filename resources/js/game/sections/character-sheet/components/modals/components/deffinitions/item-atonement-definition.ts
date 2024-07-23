export default interface ItemAtonementDefinition {
    atonements: { [key: string]: number };
    elemental_damage: {
        name: string;
        amount: number;
    };
}
