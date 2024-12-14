import React from "react";
import BasicCardProperties from "./types/basic-card-properties";

export default class BasicClosableCard extends React.Component<
    BasicCardProperties,
    any
> {
    constructor(props: BasicCardProperties) {
        super(props);
    }

    appendAdditionalClasses(): string {
        if (this.props.additionalClasses) {
            return this.props.additionalClasses;
        }

        return "";
    }

    render() {
        return (
            <div
                className={
                    "bg-white rounded-sm drop-shadow-md p-6 dark:bg-gray-800 dark:text-gray-400 " +
                    this.appendAdditionalClasses()
                }
            >
                <div className="text-right cursor-pointer text-red-500 relative top-[10px] right-[20px]">
                    <button onClick={this.props.close_action}>
                        <i className="fas fa-minus-circle"></i>
                    </button>
                </div>

                {this.props.children}
            </div>
        );
    }
}
