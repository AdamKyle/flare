import React, {Fragment} from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomProps from "../../lib/game/kingdoms/types/kingdom-props";
import KingdomDetails from "./kingdom-details";
import Select from "react-select";
import BuildingsTable from "./buildings/buildings-table";
import BuildingDetails from "../../lib/game/kingdoms/building-details";
import BuildingInformation from "./buildings/building-information";

export default class SmallKingdom extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            show_kingdom_details: false,
            which_selected: null,
            view_building: null,
        }
    }

    manageKingdomDetails() {
        this.setState({
            show_kingdom_details: !this.state.show_kingdom_details,
        });
    }

    showSelected(data: any) {
        this.setState({
            which_selected: data.value
        });
    }

    closeSelected() {
        this.setState({
            which_selected: null
        })
    }

    viewSelectedBuilding(building?: BuildingDetails) {
        this.setState({
            view_building: typeof building !== 'undefined' ? building : null,
        });
    }

    renderBuildings() {
        return (
            <Fragment>

                <div className='text-right cursor-pointer  text-red-500 mb-4'>
                    <button onClick={this.closeSelected.bind(this)}><i className="fas fa-minus-circle"></i></button>
                </div>

                {
                    this.state.view_building !== null ?
                        <BuildingInformation building={this.state.view_building} close={this.viewSelectedBuilding.bind(this)} />
                    :

                        <BasicCard additionalClasses={'overflow-x-auto'}>
                            <BuildingsTable buildings={this.props.kingdom.buildings}
                                            dark_tables={this.props.dark_tables}
                                            view_building={this.viewSelectedBuilding.bind(this)}
                            />
                        </BasicCard>
                }
            </Fragment>
        )
    }

    renderSelected() {
        switch(this.state.which_selected) {
            case 'buildings':
                return this.renderBuildings();
            default:
                return null
        }
    }

    render() {
        if (this.state.which_selected !== null) {
            return this.renderSelected();
        }

        return (
            <Fragment>
                <BasicCard>
                    {
                        !this.state.show_kingdom_details ?
                            <div className='grid grid-cols-2'>
                                <span><strong>Kingdom  Details</strong></span>
                                <div className='text-right cursor-pointer text-blue-500'>
                                    <button onClick={this.manageKingdomDetails.bind(this)}><i className="fas fa-plus-circle"></i></button>
                                </div>
                            </div>
                        :
                            <Fragment>
                                <div className='grid grid-cols-2 mb-5'>
                                    <span><strong>Kingdom  Details</strong></span>
                                    <div className='text-right cursor-pointer text-red-500'>
                                        <button onClick={this.manageKingdomDetails.bind(this)}><i className="fas fa-minus-circle"></i></button>
                                    </div>
                                </div>

                                <KingdomDetails kingdom={this.props.kingdom} />
                            </Fragment>
                    }
                </BasicCard>

                <div className='mt-4'>
                    <Select
                        onChange={this.showSelected.bind(this)}
                        options={[
                            {
                                label: 'Building Management',
                                value: 'buildings',
                            },
                            {
                                label: 'Unit Management',
                                value: 'units',
                            }
                        ]}
                        menuPosition={'absolute'}
                        menuPlacement={'bottom'}
                        styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                        menuPortalTarget={document.body}
                        value={[
                            {label: 'Please Select Section', value: ''}
                        ]}
                    />
                </div>
            </Fragment>
        )
    }
}
