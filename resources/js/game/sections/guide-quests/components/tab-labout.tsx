import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import GuideQuestLayoutProps from "./types/guide-quest-layout-props";

export default class TabLayout extends React.Component<
    GuideQuestLayoutProps,
    {}
> {
    private tabs: { name: string; key: string }[];

    constructor(props: GuideQuestLayoutProps) {
        super(props);

        this.tabs = [
            {
                key: "story",
                name: "Story",
            },
            {
                key: "information",
                name: "Information",
            },
            {
                key: "desktop-instructions",
                name: "Desktop Instructions",
            },
        ];

        if (this.props.is_small) {
            this.tabs.pop();

            this.tabs.push({
                key: "mobile-instructions",
                name: "Mobile Instructions",
            });
        }
    }

    render() {
        return (
            <Tabs tabs={this.tabs}>
                <TabPanel key={"story"}>
                    <div
                        className={
                            "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4"
                        }
                    >
                        <div
                            dangerouslySetInnerHTML={{
                                __html: this.props.intro_text,
                            }}
                        />
                    </div>
                </TabPanel>
                <TabPanel key={"information"}>
                    <div
                        className={
                            "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                        }
                    >
                        <div
                            dangerouslySetInnerHTML={{
                                __html: this.props.instructions,
                            }}
                        />
                    </div>
                </TabPanel>
                <TabPanel key={"desktop-instructions"}>
                    <div
                        className={
                            "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                        }
                    >
                        <div
                            dangerouslySetInnerHTML={{
                                __html: this.props.desktop_instructions,
                            }}
                        />
                    </div>
                </TabPanel>
            </Tabs>
        );
    }
}
