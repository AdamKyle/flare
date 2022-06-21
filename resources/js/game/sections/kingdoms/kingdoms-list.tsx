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

export default class KingdomsList extends React.Component<KingdomListProps, KingdomListState> {

    constructor(props: KingdomListProps) {
        super(props);

        this.state = {
            loading: true,
            dark_tables: false,
            kingdoms: [],
            selected_kingdom: null,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        this.setState({
            kingdoms: this.props.my_kingdoms,
        }, () => {
            const self = this;

            setTimeout(function(){
                self.setState({
                    loading: false,
                })
            }, 500);
        });
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
                                <Table data={this.state.kingdoms} columns={buildKingdomsColumns(this.viewKingdomDetails.bind(this))} dark_table={this.state.dark_tables}/>
                            </BasicCard>
                    }
                </Fragment>

        )
    }

}
