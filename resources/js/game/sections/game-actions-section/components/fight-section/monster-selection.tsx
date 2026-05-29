import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import MonsterSelectionProps from "./types/monster-selection-props";

export default class MonsterSelection extends React.Component<
    MonsterSelectionProps,
    {}
> {
    constructor(props: MonsterSelectionProps) {
        super(props);
    }

    render() {
        return (
            <div className="relative mx-auto mt-4 w-full md:w-2/3">
                <Select
                    onChange={this.props.set_monster_to_fight}
                    options={this.props.monsters}
                    menuPosition={"absolute"}
                    menuPlacement={"bottom"}
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.props.default_monster}
                />

                <div className="mt-4 flex flex-wrap justify-center gap-2 lg:absolute lg:left-full lg:top-0 lg:mt-0 lg:ml-2 lg:flex-nowrap lg:justify-start">
                    <PrimaryButton
                        button_label={"Attack"}
                        on_click={this.props.attack}
                        disabled={this.props.is_attack_disabled}
                    />

                    {typeof this.props.close_monster_section !== "undefined" ? (
                        <DangerButton
                            button_label={"Close"}
                            on_click={this.props.close_monster_section}
                        />
                    ) : null}
                </div>
            </div>
        );
    }
}
