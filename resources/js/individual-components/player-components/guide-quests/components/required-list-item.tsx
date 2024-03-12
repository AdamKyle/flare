import React from "react";
import RequiredListItemProps from "./types/required-list-item-props";

export default class RequiredListItem extends React.Component<
    RequiredListItemProps,
    {  }
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <li className={"text-orange-600 dark:text-orange-400"}>
                {this.props.isFinished ? (
                    <i className="fas fa-check text-green-700 dark:text-green-500 mr-2"></i>
                ) : null}
                {this.props.label}: {this.props.requirement}
            </li>
        );
    }
}
