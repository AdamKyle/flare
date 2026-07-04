import { DateTime } from "luxon";

export function formatLocalDateTime(value: unknown): string {
    if (typeof value !== "string" || value.trim() === "") {
        return "—";
    }

    const date = DateTime.fromISO(value, { zone: "utc" }).toLocal();

    if (!date.isValid) {
        return "—";
    }

    return date.toFormat("yyyy-MM-dd H:mm");
}

export function isIsoDateString(value: unknown): boolean {
    if (typeof value !== "string" || value.trim() === "") {
        return false;
    }

    return DateTime.fromISO(value, { zone: "utc" }).isValid;
}
