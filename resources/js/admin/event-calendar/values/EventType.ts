export default class EventType {
    static get WEEKLY_CELESTIALS(): string {
        return "Weekly Celestials";
    }

    static get WEEKLY_CURRENCY_DROPS(): string {
        return "Weekly Currency Drops";
    }

    static get WINTER_EVENT(): string {
        return "Winter Event";
    }

    static get DELUSIONAL_MEMORIES_EVENT(): string {
        return "Delusional Memories Event";
    }

    static get RAID_EVENT(): string {
        return "Raid Event";
    }

    static is(expected: string, actual: string): boolean {
        return expected === actual;
    }

    static isEventOfYearlyTypes(eventName: string): boolean {
        const eventNames = [this.WINTER_EVENT, this.DELUSIONAL_MEMORIES_EVENT];

        return eventNames.includes(eventName);
    }
}
