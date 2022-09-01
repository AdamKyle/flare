import UnitReinforcementType from "./unit-reinforcement-type";

export default interface KingdomReinforcementType {

    kingdom_name: string;

    kingdom_id: number;

    time: number;

    units: UnitReinforcementType[]
}
