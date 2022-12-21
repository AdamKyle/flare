import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";

export default class CharacterReincarnationModal extends React.Component<AdditionalInfoModalProps, any> {


    constructor(props: AdditionalInfoModalProps) {
        super(props);
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
            >
                <div>
                    <p className='mb-4'>
                        Reincarnation is a way to slowly grow your character over time. You can read more about it <a href='/information/reincarnation' target="_blank">
                            here <i
                            className="fas fa-external-link-alt"></i>
                        </a>.
                    </p>
                    <p className='mb-4'>
                        The gist is simple, reincarnate your character and start back at level one but keep 20% of your raw stats at the time
                        or reincarnation.
                    </p>
                    <p className='mb-4'>
                        You will only increase your Base Stat and Damage Mod when your character base stats reach 999,999 and you
                        cannot reincarnate anymore but can technically continue leveling Doing so, leveling, will increase these
                        additional modifiers which are then applied to your modified stats. This gives incentive to
                        keep leveling even after maxing reincarnation.
                    </p>
                    <dl>
                        <dt>Reincarnated Times</dt>
                        <dd>{this.props.character.reincarnated_times !== null ? formatNumber(this.props.character.reincarnated_times) : 0}</dd>
                        <dt>Reincarnation Stat Bonus (pts.)</dt>
                        <dd>{this.props.character.reincarnated_stat_increase !== null ? formatNumber(this.props.character.reincarnated_stat_increase) : 0}</dd>
                        <dt>Base Stat Mod</dt>
                        <dd>{formatNumber(this.props.character.base_stat_mod)}%</dd>
                        <dt>Base Damage Stat Mod</dt>
                        <dd>{formatNumber(this.props.character.base_damage_stat_mod)}%</dd>
                        <dt>XP Penalty</dt>
                        <dd>{this.props.character.xp_penalty !== null ? (this.props.character.xp_penalty * 100).toFixed(0) : 0}%</dd>
                    </dl>
                </div>
            </Dialogue>
        );
    }
}
