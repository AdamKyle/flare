import IconType from "../icon-type";

export default interface GamblingSectionState {

    loading: boolean;

    icons: IconType[]|[];

    spinning: boolean;

    spinningIndexes: number[]|[];

    roll: number[]|[];

    roll_message: string;

    error_message: string;

    timeoutFor: number;
}
