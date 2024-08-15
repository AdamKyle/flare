import InputField from "../deffinitions/input-field";

export default interface InputFieldProps {
    sectionIndex: number;
    fieldIndex: number;
    field: InputField;
    onUpdateField: (
        sectionIndex: number,
        fieldIndex: number,
        updatedField: InputField,
    ) => void;
    onRemoveField: (sectionIndex: number, fieldIndex: number) => void;
}
