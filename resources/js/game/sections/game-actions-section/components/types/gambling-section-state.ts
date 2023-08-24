import IconType from "./icon-type";

export default interface GamblingSectionState {

    loading: boolean;

    icons: IconType[]|[];

    spinning: boolean;

    spinningIndexes: number[]|[];

    roll: number[]|[];

    roll_message: string | null;

    error_message: string | null;

    timeoutFor: number;

    cost: number;
}
