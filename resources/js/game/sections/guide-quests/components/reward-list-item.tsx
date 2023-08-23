import React from "react";
import { formatNumber } from "../../../lib/game/format-number";

export default class RewardListItem extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <li className={"text-green-600 dark:text-green-400"}>
                {this.props.label}: {formatNumber(this.props.value)}
            </li>
        )
    }
}
