export default interface Field {
    type: "text" | "select" | "markdown" | "radio" | "checkbox";
    label: string;
    options?: string[];
}
