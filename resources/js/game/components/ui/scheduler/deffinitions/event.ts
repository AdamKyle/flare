export default interface Event {
    event_id: number;
    title: string;
    start: Date;
    end: Date,
    disabled?: boolean;
}
