import React from "react";
import ErrorMessageProps from "./types/error-message-props";

export default class ErrorMessage extends React.Component<
ErrorMessageProps,
    {}
> {
    constructor(props: ErrorMessageProps) {
        super(props);
    }

    render() {
        if (this.props.error_message === null) {
            return;
        }

        return (
            <div className="mb-4 italic text-center text-red-700 dark:text-red-500 text-lg">
                {this.props.error_message}
            </div>
        );
    }
}
