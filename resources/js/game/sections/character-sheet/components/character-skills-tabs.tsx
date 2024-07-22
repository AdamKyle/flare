import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import Skills from "./tabs/skill-tabs/skills";
import KingdomPassives from "./tabs/skill-tabs/kingdom-passives";
import CraftingSkills from "./tabs/skill-tabs/crafting-skills";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import CharacterSkillTabsState from "../../../lib/game/character-sheet/types/skills/character-skill-tabs-state";
import { watchForDarkModeSkillsChange } from "../../../lib/game/dark-mode-watcher";
import CharacterSkillTabsProps from "../../../lib/game/character-sheet/types/skills/character-skill-tabs-props";

export default class CharacterSkillsTabs extends React.Component<
    CharacterSkillTabsProps,
    CharacterSkillTabsState
> {
    private tabs: { name: string; key: string }[];

    private updateCharacterSkills: any;

    constructor(props: CharacterSkillTabsProps) {
        super(props);

        this.tabs = [
            {
                key: "skills",
                name: "Skills",
            },
            {
                key: "crafting",
                name: "Crafting Skills",
            },
            {
                key: "kingdom-passives",
                name: "Kingdom Passives",
            },
        ];

        this.state = {
            loading: true,
            dark_tables: false,
            skills: null,
        };

        // @ts-ignore
        this.updateCharacterSkills = Echo.private(
            "update-skill-" + this.props.user_id,
        );
    }

    componentDidMount() {
        watchForDarkModeSkillsChange(this);

        if (this.props.finished_loading) {
            new Ajax()
                .setRoute("character/skills/" + this.props.character_id)
                .doAjaxCall(
                    "get",
                    (result: AxiosResponse) => {
                        this.setState({
                            skills: result.data,
                            loading: false,
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);
                    },
                );
        }

        // @ts-ignore
        this.updateCharacterSkills.listen(
            "Game.Skills.Events.UpdateCharacterSkills",
            (event: any) => {
                let skills = JSON.parse(JSON.stringify(this.state.skills));

                if (event.trainingSkills.length > 0) {
                    skills.training_skills = event.trainingSkills;
                }

                if (event.craftingSkills.length > 0) {
                    skills.crafting_skills = event.craftingSkills;
                }

                this.setState({
                    skills: skills,
                });
            },
        );
    }

    updateSkills(skills: any) {
        if (typeof skills !== "undefined") {
            let stateSkills = JSON.parse(JSON.stringify(this.state.skills));

            const keys = Object.keys(skills);

            stateSkills[keys[0]] = skills[keys[0]];

            this.setState({
                skills: stateSkills,
            });
        }
    }

    render() {
        if (this.state.loading || this.state.skills === null) {
            return (
                <div className="my-4">
                    <ComponentLoading />
                </div>
            );
        }

        console.log("rendering character-skills-tab");

        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={"skills"}>
                    <Skills
                        trainable_skills={this.state.skills.training_skills}
                        dark_table={this.state.dark_tables}
                        is_dead={this.props.is_dead}
                        update_skills={this.updateSkills.bind(this)}
                        character_id={this.props.character_id}
                        is_automation_running={this.props.is_automation_running}
                    />
                </TabPanel>
                <TabPanel key={"crafting"}>
                    <CraftingSkills
                        crafting_skills={this.state.skills.crafting_skills}
                        dark_table={this.state.dark_tables}
                    />
                </TabPanel>
                <TabPanel key={"kingdom-passives"}>
                    <KingdomPassives
                        is_dead={this.props.is_dead}
                        character_id={this.props.character_id}
                        is_automation_running={this.props.is_automation_running}
                    />
                </TabPanel>
            </Tabs>
        );
    }
}
