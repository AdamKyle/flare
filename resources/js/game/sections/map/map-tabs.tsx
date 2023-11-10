import React from "react";
import Tabs from "../../components/ui/tabs/tabs";
import TabPanel from "../../components/ui/tabs/tab-panel";

type TabDefinition = {key: string; name:string;}[];

export default class MapTabs extends React.Component<any, any> {

    private tabs: TabDefinition;

    constructor(props: any) {
        super(props);

        this.tabs = [
            {
                key: "map",
                name: "Map",
            },
            {
                key: "event-goals",
                name: "Event Goals",
            },
        ];
    }

    render() {
        return (
            this.props.use_tabs ?
                <Tabs
                    tabs={this.tabs}
                    disabled={false}
                >
                    <TabPanel key={"map"}>
                        {this.props.children}
                    </TabPanel>
                    <TabPanel key={"event-goals"}>
                        Content here ...
                    </TabPanel>
                </Tabs>
            : this.props.children
        );
    }
}
