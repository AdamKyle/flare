import React from "react";
import { formatNumber } from "../../../../game/lib/game/format-number";
import RewardListItemProps from "./types/reward-list-item-props";

export default class RewardListItem extends React.Component<
    RewardListItemProps,
    {}
> {
    constructor(props: RewardListItemProps) {
        super(props);
    }

    render() {
        return (
            <li className={"text-green-600 dark:text-green-400"}>
                {this.props.label}: {formatNumber(this.props.value)}
            </li>
        );
    }
}
