import React from "react";
import SeperatorProps from "./types/seperator-props";
import clsx from "clsx";

export default class Seperator extends React.Component<SeperatorProps> {
    constructor(props: SeperatorProps) {
        super(props);
    }

    render() {
        return (
            <div
                className={clsx(
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    this.props.additional_css,
                )}
            ></div>
        );
    }
}
