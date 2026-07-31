export default interface ElementalAtonementType {
    atonements: Record<string, number>;
    highest_element: {
        name: string;
        damage: number;
    };
}
