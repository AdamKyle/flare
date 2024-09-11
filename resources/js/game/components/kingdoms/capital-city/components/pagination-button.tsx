import React from "react";
import clsx from "clsx";
import PaginationButtonProps from "../types/components/pagination-button-props";
import PaginationButtonState from "../types/components/pagination-button-state";

export default class PaginationButton extends React.Component<PaginationButtonProps, PaginationButtonState> {
    constructor(props: PaginationButtonProps) {
        super(props);
    }

    handleClick = () => {
        this.props.on_page_change(this.props.page_number);
    };

    render() {
        const buttonClasses = clsx(
            "px-4 py-2 mx-1 rounded",
            {
                "bg-blue-500 text-white": this.props.page_number === this.props.current_page,
                "bg-gray-200 text-gray-700": this.props.page_number !== this.props.current_page,
                "hover:bg-blue-400": this.props.page_number === this.props.current_page,
            },
            "focus:outline-none focus:ring-2 focus:ring-blue-500"
        );

        return (
            <button
                onClick={this.handleClick}
                className={buttonClasses}
            >
                {this.props.page_number}
            </button>
        );
    }
}
