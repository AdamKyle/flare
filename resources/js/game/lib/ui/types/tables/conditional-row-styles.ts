import BuildingDetails from "../../../game/kingdoms/building-details";

export default interface ConditionalRowStyles {
    when: (row: BuildingDetails) => boolean;

    style: { backgroundColor: string; color: string; };
}
