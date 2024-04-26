import React from "react";
import { AdditionalInfoProps } from "../../../../sections/character-sheet/components/types/additional-info-props";
import CharacterClassRanks from "../../../../sections/character-sheet/components/character-class-ranks";
import CharacterClassRankSpecialtiesSection from "./character-class-rank-specialties-section";
import DropDown from "../../../ui/drop-down/drop-down";
import clsx from "clsx";

export default class CharacterClassRanksSection extends React.Component<
    AdditionalInfoProps,
    any
> {
    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            class_rank_type_to_show: "",
            class_special_type_to_show: "",
        };
    }

    setFilterTypeForClassRanks(type: string): void {
        this.setState({
            class_rank_type_to_show: type,
        });
    }

    setFilterTypeForClassRankSpecialties(type: string): void {
        this.setState({
            class_special_type_to_show: type,
        });
    }

    createTypeFilterDropDownForClassRanks() {
        return [
            {
                name: "Class Ranks",
                icon_class: "ra ra-player-pyromaniac",
                on_click: () => this.setFilterTypeForClassRanks("class-ranks"),
            },
            {
                name: "Class Masteries",
                icon_class: "ra ra-player-lift",
                on_click: () =>
                    this.setFilterTypeForClassRanks("class-masteries"),
            },
        ];
    }

    createTypeFilterForDropDownForClassMasteries() {
        return [
            {
                name: "Class Specialties",
                icon_class: "ra ra-player-pyromaniac",
                on_click: () =>
                    this.setFilterTypeForClassRankSpecialties(
                        "class-specialties",
                    ),
            },
            {
                name: "Equipped Specials",
                icon_class: "ra ra-player-lift",
                on_click: () =>
                    this.setFilterTypeForClassRankSpecialties(
                        "equipped-specials",
                    ),
            },
            {
                name: "Your Other Specials",
                icon_class: "ra ra-player-lift",
                on_click: () =>
                    this.setFilterTypeForClassRankSpecialties(
                        "other-specialties",
                    ),
            },
        ];
    }

    renderSelectedType() {
        switch (this.state.class_rank_type_to_show) {
            case "class-ranks":
                return <CharacterClassRanks character={this.props.character} />;
            case "class-masteries":
                return (
                    <CharacterClassRankSpecialtiesSection
                        view_port={0}
                        is_open={true}
                        manage_modal={() => {}}
                        title={""}
                        character={this.props.character}
                        finished_loading={true}
                        selected_type={this.state.class_special_type_to_show}
                    />
                );
            default:
                return <CharacterClassRanks character={this.props.character} />;
        }
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        return (
            <div>
                <div className="flex flex-row flex-wrap">
                    <div className="my-4 max-w-full md:max-w-[25%]">
                        <DropDown
                            menu_items={this.createTypeFilterDropDownForClassRanks()}
                            button_title={"Class Rank Type"}
                        />
                    </div>
                    <div
                        className={clsx("my-4 max-w-full md:max-w-[25%] ml-4", {
                            hidden:
                                this.state.class_rank_type_to_show !==
                                "class-masteries",
                        })}
                    >
                        <DropDown
                            menu_items={this.createTypeFilterForDropDownForClassMasteries()}
                            button_title={"Class Masteries"}
                        />
                    </div>
                </div>
                {this.renderSelectedType()}
            </div>
        );
    }
}
