import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "./tops-stat-list";
import TopsProfilePanelProps from "../types/tops-profile-panel-props";
import TopsValue from "../types/tops-value";

export default class TopsProfilePanel extends React.Component<TopsProfilePanelProps> {
    render() {
        const data = this.props.data ?? {};
        const fieldTypes = this.props.fieldTypes ?? {};
        const items = Object.keys(data)
            .filter(
                (key: string) =>
                    typeof data[key] !== "object" || data[key] === null,
            )
            .map((key: string) => ({
                label: key.replaceAll("_", " "),
                value: data[key] as TopsValue,
                type: fieldTypes[key],
            }));

        return (
            <BasicCard additionalClasses="my-4">
                <section aria-live="polite">
                    <h2 className="mb-3 text-lg font-semibold">
                        {this.props.title}
                    </h2>
                    <TopsStatList items={items} />
                </section>
            </BasicCard>
        );
    }
}
