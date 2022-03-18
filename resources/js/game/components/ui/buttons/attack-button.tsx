import React from "react";

export default class AttackButton extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

    }

    render() {
        return (
            <button type='button' className={'rounded-full w-10 h-10 mx-2 ' + this.props.additional_css}>
                <i className={this.props.icon_class}></i>
            </button>
        )
    }
}
