import { InputType } from "./input-type";

export default interface InputField {
    type: InputType;
    label: string;
    options?: string[];
}
