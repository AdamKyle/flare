export default interface KingdomDamageSlotItems {
    id: number;

    amount: number;

    item: {
        affix_name: string;

        kingdom_damage: number;
    };
}
