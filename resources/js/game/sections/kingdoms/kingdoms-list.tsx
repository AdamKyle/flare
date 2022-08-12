import React, {Fragment} from "react";
import KingdomListProps from "../../lib/game/kingdoms/types/kingdom-list-props";
import ComponentLoading from "../../components/ui/loading/component-loading";
import Table from "../../components/ui/data-tables/table";
import {buildKingdomsColumns} from "../../lib/game/kingdoms/build-kingdoms-columns";
import KingdomDetails from "../../lib/game/kingdoms/kingdom-details";
import {watchForDarkModeTableChange} from "../../lib/game/dark-mode-watcher";
import KingdomListState from "../../lib/game/kingdoms/types/kingdom-list-state";
import BasicCard from "../../components/ui/cards/basic-card";
import Kingdom from "./kingdom";
import SmallKingdom from "./small-kingdom";
import {isEqual} from "lodash";

export default class KingdomsList extends React.Component<KingdomListProps, KingdomListState> {

    constructor(props: KingdomListProps) {
        super(props);

        this.state = {
            loading: true,
            dark_tables: false,
            selected_kingdom: null,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        const self = this;

        setTimeout(function(){
            self.setState({
                loading: false,
            })
        }, 500);
    }

    componentDidUpdate() {
        const foundKingdom = this.props.my_kingdoms.filter((kingdom: KingdomDetails) => {
            if (this.state.selected_kingdom === null) {
                return;
            }

            return kingdom.id === this.state.selected_kingdom.id;
        });

        if (foundKingdom.length > 0) {
            const kingdom: KingdomDetails = foundKingdom[0];

            if (!isEqual(kingdom, this.state.selected_kingdom)) {
                this.setState({
                    selected_kingdom: kingdom
                })
            }
        }
    }

    viewKingdomDetails(kingdom: KingdomDetails) {
        this.setState({
            selected_kingdom: kingdom,
        });
    }

    closeKingdomDetails() {
        this.setState({
            selected_kingdom: null,
        });
    }

    createConditionalRowStyles() {
        return [
            {
                when: (row: KingdomDetails) => row.is_protected,
                style: {
                    backgroundColor: '#49b4fd',
                    color: 'white',
                }
            }
        ];
    }

    render() {
        if (this.state.loading) {
            return (
                <BasicCard>
                    <ComponentLoading />
                </BasicCard>
            );
        }

        return (
                <Fragment>
                    {
                        this.state.selected_kingdom ?
                            this.props.view_port < 1600 ?
                                <SmallKingdom close_details={this.closeKingdomDetails.bind(this)} kingdom={this.state.selected_kingdom} dark_tables={this.state.dark_tables} />
                            :
                                <Kingdom close_details={this.closeKingdomDetails.bind(this)} kingdom={this.state.selected_kingdom} dark_tables={this.state.dark_tables} />
                        :
                            <BasicCard additionalClasses={'overflow-x-scroll'}>
                                <Table data={this.props.my_kingdoms}
                                       columns={buildKingdomsColumns(this.viewKingdomDetails.bind(this))}
                                       dark_table={this.state.dark_tables}
                                       conditional_row_styles={this.createConditionalRowStyles()}
                                />
                            </BasicCard>
                    }
                </Fragment>

        )
    }

}