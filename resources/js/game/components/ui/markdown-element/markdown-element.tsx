import React, { Component } from "react";
import MarkdownEditor from "@uiw/react-markdown-editor";
import debounce from "lodash/debounce";
import { DebouncedFunction } from "./types/debounc";
import MarkdownElementProps from "./types/markdown-element-props";
import MarkdownElementState from "./types/markdown-element-state";

export default class MarkdownElement extends Component<
    MarkdownElementProps,
    MarkdownElementState
> {
    private debouncedHandleChange: DebouncedFunction;

    constructor(props: MarkdownElementProps) {
        super(props);
        this.state = {
            content: props.initialValue || "",
        };

        // Debounce function to handle delay in updating state
        this.debouncedHandleChange = debounce(this.handleChange, 500);
    }

    handleChange = (content: string) => {
        this.setState({ content });
        // Call the parent callback if provided
        if (this.props.onChange) {
            this.props.onChange(content);
        }
    };

    handleEditorChange = (content: string) => {
        this.debouncedHandleChange(content);
    };

    render() {
        return (
            <MarkdownEditor
                value={this.state.content}
                onChange={this.handleEditorChange}
            />
        );
    }
}
