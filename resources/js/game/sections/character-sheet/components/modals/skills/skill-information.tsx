import React, {Fragment} from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import clsx from "clsx";
import {upperFirst} from "lodash";

export default class SkillInformation extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    getFilteredFields() {
        const validFields = [
            'unit_time_reduction',
            'building_time_reduction',
            'unit_movement_time_reduction',
            'base_damage_mod',
            'base_healing_mod',
            'base_ac_mod',
            'fight_timeout_mod',
            'move_timeout_mod',
        ]

        return validFields.filter((field: string) => {
            return this.props.skill[field] > 0.0;
        });
    }

    iSkillDetailsEmpty() {
        return !(this.getFilteredFields().length > 0);
    }

    renderDetails() {
        return this.getFilteredFields().map((attributeName: string) => {
            return (
                <Fragment>
                    <dt>{upperFirst(attributeName.replaceAll('_', ' '))}</dt>
                    <dd>{(this.props.skill[attributeName] * 100).toFixed(2)}%</dd>
                </Fragment>
            )
        });
    }

    renderSkillDetails() {
        return (
            <dl>
                {this.renderDetails()}
            </dl>
        )

    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.skill.name}
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

                <div className={clsx(
                    {'grid gap-2 md:grid-cols-2 md:gap-4': !this.iSkillDetailsEmpty()}
                )}>
                    <div>
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
                    </div>
                    {
                        !this.iSkillDetailsEmpty() ?
                            <div>{this.renderSkillDetails()}</div>
                        : null
                    }
                </div>



            </Dialogue>
        );
    }
}
