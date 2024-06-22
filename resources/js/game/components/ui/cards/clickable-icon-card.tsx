import React from "react";
import ClickableIconCardProps from "./types/clickable-icon-card-props";

export default class ClickableIconCard extends React.Component<
    ClickableIconCardProps,
    {}
> {
    constructor(props: ClickableIconCardProps) {
        super(props);
    }

    render() {
        return (
            <div
                className="mb-4 shadow-lg rounded-lg bg-white mx-auto m-8 p-4 flex dark:bg-gray-700
            dark:text-gray-200 hover:bg-green-100 dark:hover:bg-green-500 cursor-pointer"
                onClick={this.props.on_click}
            >
                <div className="pr-2">
                    <i
                        className={
                            this.props.icon_class + " relative top-[5px]"
                        }
                    />
                </div>
                <div>
                    <div className="text-lg pb-2">{this.props.title}</div>
                    <div className="text-md">{this.props.children}</div>
                </div>
            </div>
        );
    }
}
