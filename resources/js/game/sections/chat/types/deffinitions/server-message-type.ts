export default interface ServerMessageType {
    id: string;
    message: string;
    event_id: number;
    source?: string | null;
    item_id?: number | null;
    link_text?: string | null;
    time_stamp: string;
}
