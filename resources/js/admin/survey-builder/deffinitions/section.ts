import InputField from "./input-field";

export default interface Section {
    title: string;
    description?: string;
    input_types: InputField[];
}
