import React from "react";
import clsx from "clsx";

export default class AttackButton extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <button
                type="button"
                className={"w-10 h-10 mx-2 " + this.props.additional_css}
                onClick={this.props.on_click}
                disabled={this.props.disabled}
            >
                <i className={this.props.icon_class}></i>
            </button>
        );
    }
}
