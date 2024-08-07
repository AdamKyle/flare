import React, { Component } from "react";
import {
    MDXEditor,
    headingsPlugin,
    quotePlugin,
    listsPlugin,
    thematicBreakPlugin,
    UndoRedo,
    BoldItalicUnderlineToggles,
    toolbarPlugin,
    linkPlugin,
    linkDialogPlugin,
    tablePlugin,
    InsertTable,
    BlockTypeSelect,
} from "@mdxeditor/editor";
import debounce from "lodash/debounce";
import { DebouncedFunction } from "./types/debounc";
import MarkdownElementProps from "./types/markdown-element-props";
import MarkdownElementState from "./types/markdown-element-state";

import "@mdxeditor/editor/style.css";

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
            <MDXEditor
                contentEditableClassName={"prose"}
                markdown={this.state.content}
                onChange={this.handleEditorChange}
                plugins={[
                    headingsPlugin(),
                    listsPlugin(),
                    quotePlugin(),
                    thematicBreakPlugin(),
                    linkPlugin(),
                    linkDialogPlugin(),
                    tablePlugin(),
                    toolbarPlugin({
                        toolbarContents: () => (
                            <>
                                {" "}
                                <UndoRedo />
                                <BlockTypeSelect />
                                <BoldItalicUnderlineToggles />
                                <InsertTable />
                            </>
                        ),
                    }),
                ]}
            />
        );
    }
}
