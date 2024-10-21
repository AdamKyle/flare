import { CancellationType } from "../enums/cancellation-type";
import Unit from "./unit";

export default interface UnitRecruitmentCancellation {
    open: boolean;
    type: CancellationType | null;
    unit_details: Unit | null;
    kingdom_id: number | null;
    queue_id: number | null;
}
