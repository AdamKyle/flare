import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import Skills from "./tabs/skill-tabs/skills";
import KingdomPassives from "./tabs/skill-tabs/kingdom-passives";
import CraftingSkills from "./tabs/skill-tabs/crafting-skills";
import MiscSkills from "./tabs/skill-tabs/misc-skills";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import CharacterSkillTabsState from "../../../lib/game/character-sheet/types/skills/character-skill-tabs-state";

export default class CharacterSkillsTabs extends React.Component<any, CharacterSkillTabsState> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'skills',
            name: 'Skills'
        },{
            key: 'crafting',
            name: 'Crafting Skills'
        },{
            key: 'kingdom-passives',
            name: 'Kingdom Passives',
        }];

        this.state = {
            loading: true,
            skills: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/skills/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                skills: result.data,
                loading: false,
            });
        }, (error: AxiosError) => {
            console.log(error);
        });
    }

    render() {

        if (this.state.loading || this.state.skills === null) {
            return <ComponentLoading />
        }

        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'skills'}>
                    <Skills trainable_skills={this.state.skills.training_skills} />
                </TabPanel>
                <TabPanel key={'crafting'}>
                    <CraftingSkills crafting_skills={this.state.skills.crafting_skills} />
                </TabPanel>
                <TabPanel key={'kingdom-passives'}>
                    <KingdomPassives />
                </TabPanel>
            </Tabs>
        )
    }

}
