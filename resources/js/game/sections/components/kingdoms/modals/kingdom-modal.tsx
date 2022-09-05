import React, {Fragment} from "react";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import KingdomModalProps from "../../../../lib/game/types/map/kingdom-pins/modals/kingdom-modal-props";
import KingdomModalState from "../../../../lib/game/types/map/kingdom-pins/modals/kingdom-modal-state";
import {formatNumber} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import KingdomDetailsType from "../../../../lib/game/map/types/kingdom-details";
import KingdomDetails from "./components/kingdom-details";

export default class KingdomModal extends React.Component<KingdomModalProps, KingdomModalState> {

    constructor(props: any) {
        super(props);

        this.state = {
            can_afford: false,
            distance: 0,
            cost: 0,
            time_out: 0,
            x: 0,
            y: 0,
            loading: true,
            title: '',
            npc_owned: false,
            action_in_progress: false,
            can_attack_kingdom: false,
        }
    }

    updateLoading(kingdomDetails: KingdomDetailsType) {
        const costState = fetchCost(kingdomDetails.x_position, kingdomDetails.y_position, this.props.character_position, this.props.currencies);



        const newState = {...costState, ...{
            loading: false,
            x: kingdomDetails.x_position,
            y: kingdomDetails.y_position,
            title: this.buildTitle(kingdomDetails),
            can_attack_kingdom: kingdomDetails.is_npc_owned || kingdomDetails.is_enemy_kingdom || !kingdomDetails.is_protected,
            npc_owned: kingdomDetails.is_npc_owned,
        }};

        const state = JSON.parse(JSON.stringify(this.state));

        this.setState({...state, ...newState});
    }

    teleportDisabled(): boolean {
        return this.state.cost === 0 || !this.state.can_afford || !this.props.can_move || this.props.is_automation_running || this.props.is_dead;
    }

    handleTeleport() {
        if (typeof this.props.teleport_player !== 'undefined') {
            this.props.teleport_player({
                x: this.state.x,
                y: this.state.y,
                cost: this.state.cost,
                timeout: this.state.time_out,
            });
        }

        this.props.handle_close();
    }

    buildTitle(kingdomDetails: KingdomDetailsType) {

        const title = kingdomDetails.name + ' (X/Y): ' + kingdomDetails.x_position + '/' + kingdomDetails.y_position;

        if (kingdomDetails.is_npc_owned) {
            return title + ' [NPC Owned]';
        }

        if (kingdomDetails.is_enemy_kingdom) {
            return title + ' [Enemy]';
        }

        return title;
    }


    updateActionInProgress() {
        this.setState({
            action_in_progress: !this.state.action_in_progress,
        })
    }

    closeModal() {
        this.props.handle_close();
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.state.loading ? 'One moment ...' : this.state.title}
                      primary_button_disabled={this.state.action_in_progress}
                      secondary_actions={{
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: 'Teleport',
                          handle_action: this.handleTeleport.bind(this),
                      }}
            >
                <Fragment>
                    <KingdomDetails kingdom_id={this.props.kingdom_id}
                                    character_id={this.props.character_id}
                                    update_loading={this.updateLoading.bind(this)}
                                    show_top_section={this.props.show_top_section}
                                    allow_purchase={this.state.npc_owned}
                                    update_action_in_progress={this.updateActionInProgress.bind(this)}
                                    close_modal={this.closeModal.bind(this)}
                                    can_attack_kingdom={this.state.can_attack_kingdom}
                    />
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    {
                        this.state.cost > 0 ?
                            <Fragment>
                                <h4>Teleport Details</h4>
                                <dl>
                                    <dt>Cost to teleport (gold):</dt>
                                    <dd className={clsx(
                                        {'text-gray-700': this.state.cost === 0},
                                        {'text-green-600' : this.state.can_afford && this.state.cost > 0},
                                        {'text-red-600': !this.state.can_afford && this.state.cost > 0}
                                    )}>{formatNumber(this.state.cost)}</dd>
                                    <dt>Can afford to teleport:</dt>
                                    <dd>{this.state.can_afford ? 'Yes' : 'No'}</dd>
                                    <dt>Distance (miles):</dt>
                                    <dd>{this.state.distance}</dd>
                                    <dt>Timeout (minutes):</dt>
                                    <dd>{this.state.time_out}</dd>
                                </dl>
                            </Fragment>
                        :
                            <WarningAlert>
                                You are too close to the location to be able to teleport.
                            </WarningAlert>
                    }
                </Fragment>
            </Dialogue>
        );
    }
}
