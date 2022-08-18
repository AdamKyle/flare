import React from "react";
import KingdomDetailsType from "../../../../../../lib/game/map/types/kingdom-details";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import KingdomDetailsProps from "../../../../../../lib/game/types/map/modals/components/view-details/kingdom-details-props";
import KingdomDetailsState from "../../../../../../lib/game/types/map/modals/components/view-details/kingdom-details-state";
import KingdomDetailInfo from '../../../../kingdoms/modals/components/kingdom-details'

export default class KingdomDetails extends React.Component<KingdomDetailsProps, KingdomDetailsState> {
    constructor(props: KingdomDetailsProps) {
        super(props);

        this.state = {
            title: '',
            loading: true,
            npc_owned: false,
            action_in_progress: false,
        }
    }

    updateLoading(kingdomDetails: KingdomDetailsType) {
        const newState = {loading: false, title: this.buildTitle(kingdomDetails), npc_owned: kingdomDetails.is_npc_owned};

        const state = JSON.parse(JSON.stringify(this.state));

        this.setState({...state, ...newState});
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
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.handle_close}
                      title={this.state.loading ? 'One moment ...' : this.state.title}
                      primary_button_disabled={this.state.action_in_progress}
            >
                <KingdomDetailInfo kingdom_id={this.props.kingdom_id}
                                   character_id={this.props.character_id}
                                   update_loading={this.updateLoading.bind(this)}
                                   show_top_section={this.props.show_top_section}
                                   allow_purchase={this.state.npc_owned}
                                   update_action_in_progress={this.updateActionInProgress.bind(this)}
                                   close_modal={this.closeModal.bind(this)}
                />
            </Dialogue>
        )
    }
}
