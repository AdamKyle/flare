import React from "react";
import KingdomListProps from "../../lib/game/kingdoms/types/kingdom-list-props";
import ComponentLoading from "../../components/ui/loading/component-loading";
import Table from "../../components/ui/data-tables/table";
import {buildKingdomsColumns} from "../../lib/game/kingdoms/build-kingdoms-columns";
import KingdomDetails from "../../lib/game/kingdoms/kingdom-details";
import {watchForDarkModeTableChange} from "../../lib/game/dark-mode-watcher";
import KingdomListState from "../../lib/game/kingdoms/types/kingdom-list-state";

export default class KingdomsList extends React.Component<KingdomListProps, KingdomListState> {

    constructor(props: KingdomListProps) {
        super(props);

        this.state = {
            loading: true,
            dark_tables: false,
            kingdoms: [],
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
        console.log(kingdom);
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />
        }

        return (
            <Table data={this.state.kingdoms} columns={buildKingdomsColumns(this.viewKingdomDetails.bind(this))} dark_table={this.state.dark_tables}/>
        )
    }

}
