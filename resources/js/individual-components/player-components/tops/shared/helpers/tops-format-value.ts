import { formatNumber } from "../../../../../game/lib/game/format-number";
import {
    formatLocalDateTime,
    isIsoDateString,
} from "../../../../../game/lib/game/format-local-date";
import TopsDisplayField from "../types/tops-display-field";

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

export function formatDuration(totalSeconds: number): string {
    if (!Number.isFinite(totalSeconds) || totalSeconds <= 0) {
        return "0 Seconds";
    }

    const wholeSeconds = Math.floor(totalSeconds);
    const hours = Math.floor(wholeSeconds / 3600);
    const minutes = Math.floor((wholeSeconds % 3600) / 60);
    const seconds = wholeSeconds % 60;

    if (hours > 0) {
        if (minutes > 0) {
            return hours + ":" + String(minutes).padStart(2, "0") + " Hours";
        }

        return hours + ":00 Hours";
    }

    if (minutes > 0) {
        return minutes + " " + (minutes === 1 ? "Minute" : "Minutes");
    }

    return seconds + " " + (seconds === 1 ? "Second" : "Seconds");
}

export function formatPercent(value: number): string {
    return value.toFixed(2) + "%";
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

export function formatTopsFieldValue(
    field: TopsDisplayField,
    value: unknown,
): string {
    if (isEmptyValue(value)) {
        return "—";
    }

    if (field.type === "duration" && typeof value === "number") {
        return formatDuration(value);
    }

    if (field.type === "percent" && typeof value === "number") {
        return formatPercent(value);
    }

    return formatTopsValue(value);
}

export function formatTopsCompactFieldValue(
    field: TopsDisplayField,
    value: unknown,
): string {
    if (isEmptyValue(value)) {
        return "—";
    }

    if (field.type === "duration" && typeof value === "number") {
        return formatDuration(value);
    }

    if (field.type === "percent" && typeof value === "number") {
        return formatPercent(value);
    }

    return formatTopsCompactValue(value);
}
