import { KingdomWithUnitRequests } from "../deffinitions/kingdom-with-unit-requests";
import UnitRecruitmentCancellation from "../deffinitions/unit-recruitment-cancellation";

export default interface UnitRecruitmentState {
    loading: boolean;
    unit_queues: KingdomWithUnitRequests[];
    filtered_unit_queues: KingdomWithUnitRequests[];
    search_query: string;
    error_message: string;
    success_message: string;
    open_kingdom_ids: Set<number>;
    cancellation_modal: UnitRecruitmentCancellation;
}
