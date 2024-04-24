import React from "react";
import Dialogue from "./dialogue";
import clsx from "clsx";

export default class HelpDialogue extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.props.title}
            >
                <div
                    className={clsx({
                        "max-h-[450px] overflow-x-auto":
                            typeof this.props.no_scrolling === "undefined",
                    })}
                >
                    {this.props.children}
                </div>
            </Dialogue>
        );
    }
}
