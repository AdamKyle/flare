import React from "react";

export enum InputType {
    Text = "text",
    Radio = "radio",
    Checkbox = "checkbox",
    Markdown = "markdown",
    Select = "select",
}

export interface InputField {
    type: InputType;
    label: string;
    options?: string[];
}

export interface InputFieldProps {
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

export default class InputFieldComponent extends React.Component<InputFieldProps> {
    handleFieldTypeChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
        const updatedField = {
            ...this.props.field,
            type: event.target.value as InputType,
        };
        this.props.onUpdateField(
            this.props.sectionIndex,
            this.props.fieldIndex,
            updatedField,
        );
    };

    handleFieldLabelChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const updatedField = { ...this.props.field, label: event.target.value };
        this.props.onUpdateField(
            this.props.sectionIndex,
            this.props.fieldIndex,
            updatedField,
        );
    };

    handleFieldOptionsChange = (
        event: React.ChangeEvent<HTMLTextAreaElement>,
    ) => {
        const updatedField = {
            ...this.props.field,
            options: event.target.value
                .split(",")
                .map((option) => option.trim()),
        };
        this.props.onUpdateField(
            this.props.sectionIndex,
            this.props.fieldIndex,
            updatedField,
        );
    };

    render() {
        const { field, onRemoveField, sectionIndex, fieldIndex } = this.props;
        return (
            <div
                key={`field-${sectionIndex}-${fieldIndex}`}
                className="mb-4 pl-4 border-l-4 dark:border-gray-700 border-gray-300 mt-6"
            >
                <div className="mb-4 border p-4 rounded-md bg-white dark:bg-gray-800">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Field Type:
                    </label>
                    <select
                        value={field.type}
                        onChange={this.handleFieldTypeChange}
                        className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                    >
                        {Object.values(InputType).map((type) => (
                            <option key={type} value={type}>
                                {type.charAt(0).toUpperCase() + type.slice(1)}
                            </option>
                        ))}
                    </select>

                    {field.type !== InputType.Markdown && (
                        <>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
                                Label:
                            </label>
                            <input
                                type="text"
                                value={field.label}
                                onChange={this.handleFieldLabelChange}
                                className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                            />
                        </>
                    )}

                    {(field.type === InputType.Select ||
                        field.type === InputType.Radio ||
                        field.type === InputType.Checkbox) && (
                        <>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
                                Options (comma-separated):
                            </label>
                            <textarea
                                value={field.options?.join(",") || ""}
                                onChange={this.handleFieldOptionsChange}
                                className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                            />
                        </>
                    )}

                    {field.type === InputType.Markdown && (
                        <>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">
                                Label:
                            </label>
                            <input
                                type="text"
                                value={field.label}
                                onChange={this.handleFieldLabelChange}
                                className="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-gray-300"
                            />
                        </>
                    )}

                    <button
                        onClick={() => onRemoveField(sectionIndex, fieldIndex)}
                        className="mt-4 px-4 py-2 bg-red-500 text-white font-semibold rounded-md shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Remove Field
                    </button>
                </div>
            </div>
        );
    }
}
