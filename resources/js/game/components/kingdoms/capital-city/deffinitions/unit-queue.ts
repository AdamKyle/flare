import UnitRequest from "./unit_request";

export default interface UnitQueue {
    kingdom_id: number;
    unit_requests: UnitRequest[] | [];
}
