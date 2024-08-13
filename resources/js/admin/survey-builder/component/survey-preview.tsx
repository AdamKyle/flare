import React from "react";
import { Section } from "../survey-builder";
import MarkdownElement from "../../../game/components/ui/markdown-element/markdown-element";

interface Field {
    type: "text" | "select" | "markdown" | "radio" | "checkbox";
    label: string;
    options?: string[];
}

interface SurveyPreviewProps {
    sections: Section[];
    survey_title: string;
    survey_description: string;
}

export default class SurveyPreview extends React.Component<SurveyPreviewProps> {
    previewRef: React.RefObject<HTMLDivElement>;

    constructor(props: SurveyPreviewProps) {
        super(props);
        this.previewRef = React.createRef();
    }

    componentDidMount() {
        if (this.previewRef.current) {
            this.previewRef.current.scrollIntoView({ behavior: "smooth" });
        }
    }

    renderField = (field: Field, index: number) => {
        if (!field) return null;

        switch (field.type) {
            case "radio":
                return (
                    <div key={index} className="flex flex-col space-y-2">
                        <label className="font-semibold">{field.label}</label>
                        {field.options &&
                            field.options.map((option, i) => (
                                <div
                                    key={i}
                                    className="flex items-center space-x-2"
                                >
                                    <input
                                        type="radio"
                                        id={`radio-${index}-${i}`}
                                        name={`radio-${index}`}
                                    />
                                    <label
                                        htmlFor={`radio-${index}-${i}`}
                                        className="ml-2"
                                    >
                                        {option}
                                    </label>
                                </div>
                            ))}
                    </div>
                );
            case "checkbox":
                return (
                    <div key={index} className="flex flex-col space-y-2">
                        <label className="font-semibold">{field.label}</label>
                        {field.options &&
                            field.options.map((option, i) => (
                                <div
                                    key={i}
                                    className="flex items-center space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        id={`checkbox-${index}-${i}`}
                                    />
                                    <label
                                        htmlFor={`checkbox-${index}-${i}`}
                                        className="ml-2"
                                    >
                                        {option}
                                    </label>
                                </div>
                            ))}
                    </div>
                );
            case "text":
                return (
                    <div key={index} className="flex flex-col space-y-2">
                        <label className="font-semibold">{field.label}</label>
                        <input
                            type="text"
                            className="border border-gray-300 rounded-md p-2"
                        />
                    </div>
                );
            case "select":
                return (
                    <div key={index} className="flex flex-col space-y-2">
                        <label className="font-semibold">{field.label}</label>
                        <select className="border border-gray-300 rounded-md p-2">
                            {field.options && field.options.length > 0 ? (
                                field.options.map((option, i) => (
                                    <option key={i} value={option}>
                                        {option}
                                    </option>
                                ))
                            ) : (
                                <option>No options available</option>
                            )}
                        </select>
                    </div>
                );
            case "markdown":
                return (
                    <div key={index} className="flex flex-col space-y-2">
                        <label className="font-semibold">{field.label}</label>
                        <MarkdownElement
                            on_reset={() => {}}
                            should_reset={false}
                            initialValue={""}
                            onChange={() => {}}
                        />
                    </div>
                );
            default:
                return null;
        }
    };

    render() {
        const { sections, survey_title, survey_description } = this.props;

        return (
            <div
                ref={this.previewRef}
                className="mt-8 p-4 border rounded-md shadow-md bg-white dark:bg-gray-800"
            >
                <h2 className="text-2xl font-bold mb-4">{survey_title}</h2>
                {survey_description && (
                    <p className="mb-6 text-gray-600 dark:text-gray-300">
                        {survey_description}
                    </p>
                )}

                {sections.map((section, sectionIndex) => (
                    <div key={sectionIndex} className="mb-6">
                        <h3 className="text-xl font-semibold mb-2">
                            {section.title}
                        </h3>
                        {section.description && (
                            <p className="mb-4 text-gray-500 dark:text-gray-400">
                                {section.description}
                            </p>
                        )}

                        <div className="space-y-4 pl-4 border-l-2 border-gray-300 dark:border-gray-600">
                            {section.input_types.map((field, fieldIndex) =>
                                this.renderField(field, fieldIndex),
                            )}
                        </div>
                    </div>
                ))}
            </div>
        );
    }
}
