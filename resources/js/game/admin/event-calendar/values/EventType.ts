export default class EventType {

    static get WEEKLY_CELESTIALS(): string {
        return 'Weekly Celestials';
    }

    static get MONTHLY_PVP(): string {
        return 'Monthly PVP';
    }

    static get WEEKLY_CURRENCY_DROPS(): string {
        return 'Weekly Currency Drops';
    }

    static get RAID_EVENT(): string {
        return 'Raid Event';
    }

    static is(expected: string, actual: string): boolean {
        return expected === actual;
    }
} 