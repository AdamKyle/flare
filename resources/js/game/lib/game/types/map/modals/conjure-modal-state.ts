import CelestialType from "../../actions/monster/celestial-type";

export default interface ConjureModalState {
    loading: boolean;

    celestials: CelestialType[]|[];

    selected_celestial_id: number | null;

    error_message: string;

    conjuring: boolean;
}
