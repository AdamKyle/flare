import React from "react";
import SuccessMessageProps from "./types/success-message-props";

export default class SuccessMessage extends React.Component<
    SuccessMessageProps,
    {}
> {
    constructor(props: SuccessMessageProps) {
        super(props);
    }

    render() {
        if (this.props.success_message === null) {
            return;
        }

        return (
            <div className="mb-4 italic text-center text-green-700 dark:text-green-500 text-lg">
                {this.props.success_message}
            </div>
        );
    }
}
