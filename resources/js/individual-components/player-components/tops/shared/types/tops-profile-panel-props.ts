import TopsValue from "./tops-value";

export default interface TopsProfilePanelProps {
    title: string;
    data: Record<string, TopsValue> | undefined;
    fieldTypes?: Record<
        string,
        "text" | "number" | "boolean" | "date" | "duration" | "percent"
    >;
}
