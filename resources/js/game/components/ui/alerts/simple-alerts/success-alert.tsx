import React from "react";
import AlertProps from "../../../../lib/ui/types/alerts/alert-props";

export default class SuccessAlert extends React.Component<AlertProps, any> {

    constructor(props: AlertProps) {
        super(props);
    }

    render() {
        return  (
            <div className={'border-l-2 border-l-green-500 bg-green-50 dark:bg-green-600/[.15] p-4 pl-[10px] ' + this.props.additional_css}>
                <div className="flex justify-between"
                >
                    <span className="self-center">{this.props.children}</span>

                    {
                        typeof this.props.close_alert !== 'undefined' ?
                            <strong className="text-xl align-center cursor-pointer text-green-500" onClick={this.props.close_alert}>&times;</strong>
                        : null
                    }

                </div>
            </div>
        )
    }

}
