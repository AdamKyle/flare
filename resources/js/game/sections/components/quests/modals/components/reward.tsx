import React, {Fragment} from "react";
import {formatNumber} from "../../../../../lib/game/format-number";

export default class Reward extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <dl>
                {
                    this.props.quest.reward_xp !== null ?
                        <Fragment>
                            <dt>XP Reward</dt>
                            <dd>{formatNumber(this.props.quest.reward_xp)}</dd>
                        </Fragment>
                        : null
                }
                {
                    this.props.quest.reward_gold !== null ?
                        <Fragment>
                            <dt>Gold Reward</dt>
                            <dd>{formatNumber(this.props.quest.reward_gold)}</dd>
                        </Fragment>
                        : null
                }
                {
                    this.props.quest.reward_gold_dust !== null ?
                        <Fragment>
                            <dt>Gold Dust Reward</dt>
                            <dd>{formatNumber(this.props.quest.reward_gold_dust)}</dd>
                        </Fragment>
                        : null
                }
                {
                    this.props.quest.reward_shards !== null ?
                        <Fragment>
                            <dt>Shards Reward</dt>
                            <dd>{formatNumber(this.props.quest.reward_shards)}</dd>
                        </Fragment>
                        : null
                }
                {
                    this.props.quest.unlocks_skill ?
                        <Fragment>
                            <dt>Unlocks New Skill</dt>
                            <dd>{this.props.quest.unlocks_skill_name}</dd>
                        </Fragment>
                        : null
                }
                {
                    this.props.quest.reward_item !== null ?
                        <Fragment>
                            <dt>Item reward</dt>
                            <dd>
                                <a href={"/items/" + this.props.quest.reward_item.id} target="_blank">
                                    {this.props.quest.reward_item.name} <i
                                    className="fas fa-external-link-alt"></i>
                                </a>
                            </dd>
                        </Fragment>
                    : null
                }
            </dl>
        )
    }

}
