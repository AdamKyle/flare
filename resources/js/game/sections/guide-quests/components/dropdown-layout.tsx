import React, { Fragment } from "react";
import DropDown from "../../../components/ui/drop-down/drop-down";
import GuideQuestLayoutProps from "./types/guide-quest-layout-props";
import GuideQuestLayoutState from "./types/guide-quest-layout-state";

export default class DropDownLayout extends React.Component<
    GuideQuestLayoutProps,
    GuideQuestLayoutState
> {
    constructor(props: GuideQuestLayoutProps) {
        super(props);

        this.state = {
            section: "intro_text",
        };
    }

    switchInformation(type: string): void {
        this.setState({
            section: type,
        });
    }

    renderDropDownOptions(): {
        name: string;
        icon_class: string;
        on_click: () => void;
    }[] {
        return [
            {
                name: "Story",
                icon_class: "fas fa-book-reader",
                on_click: () => this.switchInformation("intro_text"),
            },
            {
                name: "Information",
                icon_class: "fas fa-info-circle",
                on_click: () => this.switchInformation("instructions"),
            },
            {
                name: "Desktop Instructions",
                icon_class: "fas fa-desktop",
                on_click: () => this.switchInformation("desktop_instructions"),
            },
            {
                name: "Mobile Instructions",
                icon_class: "fas fa-mobile",
                on_click: () => this.switchInformation("mobile_instructions"),
            },
        ];
    }

    render() {
        type SectionKey = keyof GuideQuestLayoutProps;

        const htmlKey: SectionKey = this.state.section as SectionKey;

        return (
            <Fragment>
                <DropDown
                    menu_items={this.renderDropDownOptions()}
                    button_title={"Guide Help"}
                    selected_name={this.state.section}
                />
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <div
                    className={
                        "border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4 guide-quest-instructions"
                    }
                >
                    <div
                        dangerouslySetInnerHTML={{
                            __html: this.props[htmlKey],
                        }}
                    />
                </div>
            </Fragment>
        );
    }
}
