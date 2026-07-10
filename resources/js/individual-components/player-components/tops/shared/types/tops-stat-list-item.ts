import TopsDisplayField from "./tops-display-field";
import TopsValue from "./tops-value";

export default interface TopsStatListItem {
    label: string;
    value: TopsValue | undefined;
    type?: TopsDisplayField["type"];
}
