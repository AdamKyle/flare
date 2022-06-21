import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import BuildingsTable from "./buildings/buildings-table";
import BuildingDetails from "../../lib/game/kingdoms/building-details";
import BuildingInformation from "./buildings/building-information";

export default class Kingdom extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            building_to_view: null,
        }
    }

    manageViewBuilding(building?: BuildingDetails) {
       this.setState({
           building_to_view: typeof building !== 'undefined' ? building : null
       });
    }

    render() {

        if (this.state.building_to_view !== null) {
            return <BuildingInformation building={this.state.building_to_view} close={this.manageViewBuilding.bind(this)} />
        }

        return (
            <Fragment>
                <div className='grid md:grid-cols-2 gap-4'>
                    <BasicCard additionalClasses={'max-h-[600px]'}>
                        <div className='text-right cursor-pointer text-red-500'>
                            <button onClick={this.props.close_details}><i className="fas fa-minus-circle"></i></button>
                        </div>
                        <KingdomDetails kingdom={this.props.kingdom} />
                    </BasicCard>

                    <div>
                        <BasicCard additionalClasses={'overflow-x-scroll'}>
                            <BuildingsTable buildings={this.props.kingdom.buildings} dark_tables={this.props.dark_tables} view_building={this.manageViewBuilding.bind(this)}/>
                        </BasicCard>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <BasicCard>
                            Units
                        </BasicCard>
                    </div>

                </div>
            </Fragment>
        )
    }
}
