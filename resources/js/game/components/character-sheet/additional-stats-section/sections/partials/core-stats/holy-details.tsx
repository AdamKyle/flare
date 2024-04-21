import React from "react";

export default class HolyDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <p className='mt-3 mb-6'>
                    Holy comes from crafting Alchemy items such as Holy Oils which can then be applied to a characters
                    item
                    to increase the stats you see below, which then apply to your character over all.
                </p>
                <dl>
                    <dt>Holy Bonus:</dt>
                    <dt>{(this.props.stat_details.holy_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy Stacks:</dt>
                    <dt>{this.props.stat_details.current_stacks} / {this.props.stat_details.max_holy_stacks}</dt>
                    <dt>Holy Attack Bonus:</dt>
                    <dt>{(this.props.stat_details.holy_attack_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy AC Bonus:</dt>
                    <dt>{(this.props.stat_details.holy_ac_bonus * 100).toFixed(2)}%</dt>
                    <dt>Holy Healing Bonus:</dt>
                    <dt>{(this.props.stat_details.holy_healing_bonus * 100).toFixed(2)}%</dt>
                </dl>
                <p className='mt-4'>
                    For more information please see <a href='/information/holy-items' target='_blank'>Holy Items
                    Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        );
    }
}
