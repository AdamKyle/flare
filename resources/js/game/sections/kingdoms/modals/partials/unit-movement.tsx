import React, {Fragment} from "react";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import MoveUnits from "../../../../lib/game/kingdoms/move-units/move-units";
import UnitMovementProps from "../../../../lib/game/kingdoms/types/modals/partials/unit-movement-props";
import UnitMovementState from "../../../../lib/game/kingdoms/types/modals/partials/unit-movement-state";


export default class UnitMovement extends React.Component<UnitMovementProps, UnitMovementState> {

    private moveUnits: MoveUnits

    constructor(props: any) {
        super(props);

        this.state = {
            selected_kingdoms: [],
            selected_units: [],
        }

        this.moveUnits = new MoveUnits;
    }

    setAmountToMove(kingdomId: number, unitId: number, unitAmount: number, e: React.ChangeEvent<HTMLInputElement>) {

        const unitsToCall = this.moveUnits.setAmountToMove(this.state.selected_units, kingdomId, unitId, unitAmount, e);

        if (unitsToCall === null) {
            return;
        }

        this.setState({
            selected_units: unitsToCall
        }, () => {
            this.props.update_units_selected(unitsToCall);
        });
    }

    setKingdoms(data: any) {
        const validData = data.filter((data: any) => data.value !== 'Please select one or more kingdoms');

        let selectedKingdoms = JSON.parse(JSON.stringify(this.state.selected_kingdoms));

        selectedKingdoms = validData.map((value: any) => parseInt(value.value, 10) || 0);

        this.setState({
            selected_kingdoms: selectedKingdoms,
        }, () => {
            this.props.update_kingdoms_selected(selectedKingdoms);
        });
    }

    render() {

        return (
            <Fragment>
                {
                    this.props.kingdoms.length > 0 ?
                        <Fragment>
                            {this.moveUnits.renderKingdomSelect(this.props.kingdoms, this.state.selected_kingdoms, this.setKingdoms.bind(this))}

                            {
                                this.state.selected_kingdoms.length > 0 ?
                                    <div className='my-4 max-h-[350px] overflow-y-auto'>
                                        {this.moveUnits.getUnitOptions(
                                            this.props.kingdoms,
                                            this.state.selected_units,
                                            this.state.selected_kingdoms,
                                            this.setAmountToMove.bind(this)
                                        )}
                                    </div>

                                    : null
                            }
                        </Fragment>
                    :
                        <InfoAlert>
                            You have no units in other kingdoms to move units from or you have no other kingdoms.
                        </InfoAlert>
                }
            </Fragment>
        )
    }
}
