import React from "react";
import {AdditionalInfoProps} from "../../../../sections/character-sheet/components/types/additional-info-props";
import {formatNumber} from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from 'axios';
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import DropDown from "../../../ui/drop-down/drop-down";
import StatDetails from "./partials/core-stats/stat-details";
import HolyDetails from "./partials/core-stats/holy-details";
import AmbushAndCounterDetails from "./partials/core-stats/ambush-and-counter-details";
import VoidanceDetails from "./partials/core-stats/voidance-details";

export default class CoreCharacterStatsSection extends React.Component<AdditionalInfoProps, any> {

    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            is_loading: true,
            stat_details: [],
            error_message: '',
            stat_type_to_show: '',
        }
    }

    componentDidMount() {
        if (this.props.character === null) {
            return;
        }

        (new Ajax).setRoute('character-sheet/'+this.props.character.id+'/stat-details').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                is_loading: false,
                stat_details: response.data.stat_details
            });
        }, (error: AxiosError) => {
            this.setState({ is_loading: false});
            if (typeof error.response !== 'undefined') {
                this.setState({
                    error_message: error.response.data.message,
                });
            }
        });
    }

    setFilterType(type: string): void {
        this.setState({
            stat_type_to_show: type
        })
    }

    createTypeFilterDropDown() {
        return [
            {
                name: "Core Stats",
                icon_class: "ra ra-muscle-fat",
                on_click: () => this.setFilterType('core-stats'),
            },
            {
                name: "Holy",
                icon_class: "ra ra-level-three",
                on_click: () => this.setFilterType('holy'),
            },
            {
                name: "Ambush & Counter",
                icon_class: "ra ra-blade-bite",
                on_click: () => this.setFilterType('ambush'),
            },
            {
                name: "Voidance",
                icon_class: "ra ra-double-team",
                on_click: () => this.setFilterType('voidance'),
            },
        ];
    }

    renderSection() {
        switch (this.state.stat_type_to_show) {
            case 'core-stats':
                return <StatDetails stat_details={this.state.stat_details} character={this.props.character}/>
            case 'holy':
                return <HolyDetails stat_details={this.state.stat_details} />
            case 'ambush':
                return <AmbushAndCounterDetails stat_details={this.state.stat_details} />
            case 'voidance':
                return <VoidanceDetails stat_details={this.state.stat_details} />
            default:
                return <StatDetails stat_details={this.state.stat_details} character={this.props.character}/>
        }
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        if (this.state.is_loading) {
            return <LoadingProgressBar/>
        }

        return (
            <div>
                <div className='my-4 max-w-full md:max-w-[25%]'>
                    <DropDown
                        menu_items={this.createTypeFilterDropDown()}
                        button_title={"Stat Type"}
                    />
                </div>

                {
                    this.renderSection()
                }
            </div>
        );
    }
}
