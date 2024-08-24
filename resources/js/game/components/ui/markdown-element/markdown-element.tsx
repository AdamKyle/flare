import React, { Component, createRef } from "react";
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
    MDXEditorMethods,
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

    private editorRef: React.RefObject<MDXEditorMethods> =
        createRef<MDXEditorMethods>();

    constructor(props: MarkdownElementProps) {
        super(props);
        this.state = {
            content: props.initialValue || "",
        };

        // Debounce function to handle delay in updating state
        this.debouncedHandleChange = debounce(this.handleChange, 500);
    }

    componentDidUpdate(prevProps: MarkdownElementProps) {
        if (this.props.initialValue !== prevProps.initialValue) {
            this.setState({ content: this.props.initialValue || "" });
        }

        if (this.props.should_reset && this.editorRef.current) {
            this.editorRef.current.setMarkdown("");

            this.props.on_reset();
        }
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
        if (this.editorRef.current) {
            this.editorRef.current.setMarkdown(this.state.content);
        }

        return (
            <div className="border border-gray-300 dark:border-gray-700 rounded-md p-4 min-h-[400px] bg-white dark:bg-gray-900">
                <MDXEditor
                    contentEditableClassName="prose dark:prose-dark min-h-[350px] p-2 text-gray-900 dark:text-gray-100 caret-current"
                    markdown={this.state.content}
                    onChange={this.handleEditorChange}
                    ref={this.editorRef}
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
                                    <UndoRedo />
                                    <BlockTypeSelect />
                                    <BoldItalicUnderlineToggles />
                                    <InsertTable />
                                </>
                            ),
                        }),
                    ]}
                    placeholder="Click to start typing."
                />
            </div>
        );
    }
}
