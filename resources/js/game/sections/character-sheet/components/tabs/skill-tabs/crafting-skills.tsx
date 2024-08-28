import React, { Fragment } from "react";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import CraftingSkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/crafting-skills-props";
import SkillInformation from "../../modals/skills/skill-information";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";

export default class CraftingSkills extends React.Component<
    CraftingSkillsProps,
    any
> {
    constructor(props: CraftingSkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            skill: null,
        };
    }

    manageSkillDetails(row?: any) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== "undefined" ? row : null,
        });
    }

    buildColumns() {
        return [
            {
                name: "Name",
                selector: (row: { name: string }) => row.name,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                        className="m-auto"
                    >
                        <button
                            onClick={() => this.manageSkillDetails(row)}
                            className="underline"
                        >
                            {row.name}
                        </button>
                    </span>
                ),
            },
            {
                name: "Level",
                selector: (row: { level: number }) => row.level,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.level}/{row.max_level}
                    </span>
                ),
            },
            {
                name: "XP",
                selector: (row: { xp: number }) => row.xp,
                sortable: true,
                cell: (row: SkillType) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.xp}/{row.xp_max}
                    </span>
                ),
            },
        ];
    }

    renderIcon(name: string): JSX.Element {
        switch (name) {
            case "Gem Crafting":
                return <i className="ra ra-diamond"></i>;
            case "Trinketry":
                return <i className="ra ra-gem-pendant"></i>;
            case "Alchemy":
                return <i className="ra ra-bubbling-potion"></i>;
            case "Disenchanting":
                return <i className="ra ra-ball"></i>;
            case "Enchanting":
                return <i className="ra ra-frostfire"></i>;
            case "Spell Crafting":
                return <i className="ra ra-burning-book"></i>;
            case "Ring Crafting":
            case "Armour Crafting":
                return <i className="ra ra-anvil"></i>;
            case "Weapon Crafting":
            default:
                return <i className="ra ra-flat-hammer"></i>;
        }
    }

    renderCraftingSkills(): JSX.Element {
        const skills = this.props.crafting_skills.map(
            (trainable_skill: any, index: number) => {
                return (
                    <div key={trainable_skill.id}>
                        <div className="p-4">
                            <div className="flex items-center justify-between mb-2">
                                <span className="w-24 font-semibold">
                                    Name:
                                </span>
                                <span>
                                    <button
                                        className="text-orange-600 underline cursor-pointer dark:text-orange-300"
                                        onClick={() =>
                                            this.manageSkillDetails(
                                                trainable_skill,
                                            )
                                        }
                                    >
                                        {this.renderIcon(trainable_skill.name)}{" "}
                                        {trainable_skill.name}
                                    </button>
                                </span>
                            </div>

                            <div className="flex items-center justify-between mb-2">
                                <span className="w-24 font-semibold">
                                    Level:
                                </span>
                                <span>
                                    {trainable_skill.level}/
                                    {trainable_skill.max_level}
                                </span>
                            </div>

                            <div className="flex items-center justify-between mb-2">
                                <span className="w-24 font-semibold">XP:</span>
                                <span>
                                    {trainable_skill.xp}/
                                    {trainable_skill.xp_max}
                                </span>
                            </div>
                        </div>
                        {index < this.props.crafting_skills.length - 1 && (
                            <div className="my-3 border-b-2 border-b-gray-200 dark:border-b-gray-600"></div>
                        )}
                    </div>
                );
            },
        );

        return <div className="space-y-4">{skills}</div>;
    }

    render() {
        return (
            <Fragment>
                <div className="mb-4">
                    <InfoAlert>
                        This section will not update in real time.
                    </InfoAlert>
                </div>

                <div className={"max-w-full"}>
                    {this.renderCraftingSkills()}
                </div>

                {this.state.show_skill_details && this.state.skill !== null ? (
                    <SkillInformation
                        is_trainable={false}
                        skill={this.state.skill}
                        manage_modal={this.manageSkillDetails.bind(this)}
                        is_open={this.state.show_skill_details}
                    />
                ) : null}
            </Fragment>
        );
    }
}
