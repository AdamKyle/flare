import TopsValue from "../types/tops-value";

export function asTopsRecord(
    value: TopsValue | undefined,
): Record<string, TopsValue> {
    if (value === null || typeof value !== "object" || Array.isArray(value)) {
        return {};
    }

    return value;
}

export function asTopsRecordList(
    value: TopsValue | undefined,
): Record<string, TopsValue>[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .filter(
            (item: TopsValue) =>
                item !== null &&
                typeof item === "object" &&
                !Array.isArray(item),
        )
        .map((item: TopsValue) => item as Record<string, TopsValue>);
}
