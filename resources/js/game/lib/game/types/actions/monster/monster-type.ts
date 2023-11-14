import ElementalAtonement
    from "../../../../../sections/game-actions-section/components/deffinitions/elemental-atonement";

export default interface MonsterType {
    id: number;
    name: string;
    elemental_atonement: ElementalAtonement;
    highest_element: string| null;

}
