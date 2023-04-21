import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import CallForReinforcementsProps from "../../../lib/game/kingdoms/types/modals/call-for-reinforcements-props";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import CallForReinforcementsState from "../../../lib/game/kingdoms/types/modals/call-for-reinforcements-state";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import MoveUnits from "../../../lib/game/kingdoms/move-units/move-units";
import UnitMovement from "./partials/unit-movement";
import SelectedUnitsToCallType from "../../../lib/game/kingdoms/types/selected-units-to-call-type";


export default class CallForReinforcements extends React.Component<CallForReinforcementsProps, CallForReinforcementsState> {

    private moveUnits: MoveUnits

    constructor(props: CallForReinforcementsProps) {
        super(props);

        this.state = {
            loading: true,
            processing_unit_request: false,
            kingdoms: [],
            error_message: '',
            success_message: '',
            selected_kingdoms: [],
            selected_units: [],
        }

        this.moveUnits = new MoveUnits;
    }

    componentDidMount() {
        (new Ajax).setRoute('kingdoms/units/'+this.props.character_id+'/'+this.props.kingdom_id+'/call-reinforcements')
                  .doAjaxCall('get', (result: AxiosResponse) => {
                      this.setState({
                          loading: false,
                          kingdoms: result.data
                      })
                  }, (error: AxiosError) => {
                      this.setState({loading: false});

                      console.error(error);
                  })
    }

    callUnits() {
        this.setState({
            processing_unit_request: true,
        }, () => {
            (new Ajax).setRoute('kingdom/move-reinforcements/'+this.props.character_id+'/'+this.props.kingdom_id)
                .setParameters({units_to_move: this.state.selected_units})
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        processing_unit_request: false,
                    }, () => {
                        this.props.handle_close();
                    })
                }, (error: AxiosError) => {
                    this.setState({processing_unit_request: false});

                    console.error(error);
                });
        });

    }

    setAmountToMove(selectedUnits: SelectedUnitsToCallType[]|[]) {
        this.setState({
            selected_units: selectedUnits
        });
    }

    setKingdoms(kingdomsSelected: number[]|[]) {

        this.setState({
            selected_kingdoms: kingdomsSelected,
        })
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Call for reinforcements'}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          handle_action: this.callUnits.bind(this),
                          secondary_button_disabled: this.state.loading || (this.state.kingdoms.length === 0 && this.state.selected_units.length === 0),
                          secondary_button_label: 'Call Reinforcements',
                      }}
            >
                {
                    this.state.loading ?
                        <ComponentLoading />
                    : null
                }

                {
                    this.state.kingdoms.length > 0 ?
                        <Fragment>
                            <UnitMovement kingdoms={this.state.kingdoms}
                                          update_units_selected={this.setAmountToMove.bind(this)}
                                          update_kingdoms_selected={this.setKingdoms.bind(this)}
                            />

                            {
                                this.state.processing_unit_request ?
                                    <LoadingProgressBar />
                                : null
                            }
                        </Fragment>
                    :
                        <InfoAlert>
                            You have no units in other kingdoms to move units from or you have no other kingdoms.
                        </InfoAlert>
                }
            </Dialogue>
        )
    }
}
