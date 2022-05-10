import React from "react";
import AlertProps from "../../../../lib/ui/types/alerts/alert-props";

export default class DangerAlert extends React.Component<AlertProps, any> {

    constructor(props: AlertProps) {
        super(props);
    }

    render() {
        return  (
            <div className={'border-l-2 border-l-red-500 px-4 pl-[10px] ' + this.props.additional_css}>
                <div className="flex justify-between"
                >
                    <span className="self-center text-red-500">{this.props.children}</span>

                    {
                        typeof this.props.close_alert !== 'undefined' ?
                            <strong className="text-xl align-center cursor-pointer text-red-500" onClick={this.props.close_alert}>&times;</strong>
                        : null
                    }

                </div>
            </div>
        )
    }

}
