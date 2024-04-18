import ElementalAtonement
    from "../../../../../components/crafting/gem-crafting/deffinitions/elemental-atonement";

export default interface MonsterType {
    id: number;
    name: string;
    elemental_atonement: ElementalAtonement;
    highest_element: string| null;

}
