import { formatNumber } from "../../../../../game/lib/game/format-number";
import {
    formatLocalDateTime,
    isIsoDateString,
} from "../../../../../game/lib/game/format-local-date";

function isEmptyValue(value: unknown): boolean {
    return value === null || typeof value === "undefined" || value === "";
}

function isObjectValue(value: unknown): boolean {
    return typeof value === "object" || Array.isArray(value);
}

export function formatTopsValue(value: unknown): string {
    if (isEmptyValue(value)) {
        return "—";
    }

    if (typeof value === "boolean") {
        return value ? "Yes" : "No";
    }

    if (typeof value === "number") {
        return formatNumber(value);
    }

    if (typeof value === "string") {
        if (isIsoDateString(value)) {
            return formatLocalDateTime(value);
        }

        return value;
    }

    if (isObjectValue(value)) {
        return "—";
    }

    return "—";
}

export function formatTopsCompactValue(value: unknown): string {
    if (isEmptyValue(value)) {
        return "—";
    }

    if (typeof value === "boolean") {
        return value ? "Yes" : "No";
    }

    if (typeof value === "number") {
        if (value < 10000) {
            return formatNumber(value);
        }

        const absoluteValue = Math.abs(value);
        const maximumFractionDigits = absoluteValue >= 99_000_000_000 ? 0 : 1;

        return new Intl.NumberFormat("en-US", {
            notation: "compact",
            maximumFractionDigits,
        }).format(value);
    }

    if (typeof value === "string") {
        if (isIsoDateString(value)) {
            return formatLocalDateTime(value);
        }

        return value;
    }

    if (isObjectValue(value)) {
        return "—";
    }

    return "—";
}
