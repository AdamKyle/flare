import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import BuildingsTable from "../buildings/buildings-table";
import UnitsTable from "../units/units-table";
import BasicCard from "../../../components/ui/cards/basic-card";
import React from "react";
import KingdomQueues from "../queues/kingdom-queues";

export default class KingdomTabs extends React.Component<any, any> {
    private tabs: { name: string; key: string }[];

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "buildings",
                name: "Buildings",
            },
            {
                key: "units",
                name: "Units",
            },
            {
                name: "Queues",
                key: "current-queue",
            },
        ];
    }

    render() {
        return (
            <BasicCard additionalClasses={"h-full flex flex-col min-h-0"}>
                <div className="h-full min-h-0 flex flex-col [&>div]:h-full [&>div]:min-h-0 [&>div]:flex [&>div]:flex-col">
                    <Tabs
                        tabs={this.tabs}
                        full_width={true}
                        additonal_css={"shrink-0"}
                    >
                        <TabPanel key={"buildings"}>
                            <BuildingsTable
                                buildings={this.props.kingdom.buildings}
                                dark_tables={this.props.dark_tables}
                                buildings_in_queue={
                                    this.props.kingdom.building_queue
                                }
                                view_building={this.props.manage_view_building}
                                view_port={this.props.view_port}
                                is_automation_locked={
                                    this.props.is_automation_locked
                                }
                            />
                        </TabPanel>
                        <TabPanel key={"units"}>
                            <UnitsTable
                                units={this.props.kingdom.units}
                                buildings={this.props.kingdom.buildings}
                                dark_tables={this.props.dark_tables}
                                view_unit={this.props.manage_view_unit}
                                units_in_queue={this.props.kingdom.unit_queue}
                                current_units={this.props.kingdom.current_units}
                                is_automation_locked={
                                    this.props.is_automation_locked
                                }
                            />
                        </TabPanel>
                        <TabPanel key={"current-queue"}>
                            <KingdomQueues
                                user_id={this.props.user_id}
                                kingdom_id={this.props.kingdom.id}
                                character_id={this.props.kingdom.character_id}
                                kingdoms={this.props.kingdoms}
                                is_automation_locked={
                                    this.props.is_automation_locked
                                }
                            />
                        </TabPanel>
                    </Tabs>
                </div>
            </BasicCard>
        );
    }
}
