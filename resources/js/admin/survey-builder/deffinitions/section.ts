import { InputField } from "../component/input-field-component";

export default interface Section {
    title: string;
    description?: string;
    input_types: InputField[];
}
