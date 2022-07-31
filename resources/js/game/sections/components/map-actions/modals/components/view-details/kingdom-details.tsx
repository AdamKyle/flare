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
        }
    }

    updateLoading(kingdomDetails: KingdomDetailsType) {
        const newState = {loading: false, title: this.buildTitle(kingdomDetails)};

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

    render() {
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.handle_close}
                      title={this.state.loading ? 'One moment ...' : this.state.title}
            >
                <KingdomDetailInfo kingdom_id={this.props.kingdom_id}
                                   character_id={this.props.character_id}
                                   update_loading={this.updateLoading.bind(this)}
                                   show_top_section={this.props.show_top_section}
                />
            </Dialogue>
        )
    }
}
