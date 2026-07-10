export default interface TopsDisplayField {
    key: string;
    label: string;
    align?: "left" | "right" | "center";
    type?: "text" | "number" | "boolean" | "date" | "duration" | "percent";
}
