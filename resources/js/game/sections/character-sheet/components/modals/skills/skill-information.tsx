import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";

export default class SkillInformation extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.skill.name}
                      secondary_actions={null}
            >
                {
                    this.props.skill.is_locked ?
                        <DangerAlert additional_css={'mb-4 mt-4'}>
                            This skill is locked. You will need to complete a quest to unlock it.
                        </DangerAlert>
                    : null
                }

                <p className='mb-4'>
                    {this.props.skill.description}
                </p>

                <dl>
                    <dt>Current Level</dt>
                    <dd>{this.props.skill.level}</dd>
                    <dt>Max Level</dt>
                    <dd>{this.props.skill.max_level}</dd>
                    <dt>XP Towards</dt>
                    <dd>{this.props.skill.xp_towards !== null ? (this.props.skill.xp_towards * 100).toFixed(2) : 0.00}%</dd>
                    <dt>Skill Bonus</dt>
                    <dd>{(this.props.skill.skill_bonus * 100).toFixed(2)}%</dd>
                    <dt>Skill XP Bonus</dt>
                    <dd>{(this.props.skill.skill_xp_bonus * 100).toFixed(2)} %</dd>
                    <dt>XP</dt>
                    <dd>{formatNumber(this.props.skill.xp)} / {formatNumber(this.props.skill.xp_max)}</dd>
                </dl>

            </Dialogue>
        );
    }
}
