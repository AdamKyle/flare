import React from "react";
import Tabs from "../../../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../../../components/ui/tabs/tab-panel";
import AdditionalInformation from "../additional-information";
import CharacterResistances from "../character-resistances";
import CharacterReincarnation from "../character-reincarnation";
import CharacterClassRanks from "../character-class-ranks";
import CharacterElementalAtonement from "../character-elemental-atonement";
import {viewPortWatcher} from "../../../../../../lib/view-port-watcher";
import Select from "react-select";

export default class CharacterAdditionalStatsSection extends React.Component<any, any> {

    private tabs: {key: string, name: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'core-info',
            name: 'Base Info'
        },{
            key: 'resistance',
            name: 'Resistances',
        }, {
            key: 'reincarnation',
            name: 'Reincarnation'
        }, {
            key: 'class-ranks',
            name: 'Class Ranks',
        }, {
            key: 'elemental-atonement',
            name: 'Elemental Atonement',
        }];

        this.state = {
            small_tab_selected: '',
            view_port: 0,
        }
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    sectionDropDownOptions(): { label: string; value: string }[] {
        return this.tabs.map((tab) => ({
            label: tab.name,
            value: tab.key,
        }));
    }

    setSelected(data: any) {
        this.setState({
            small_tab_selected: data.value,
        })
    }

    getSelectedValue() {
        if (this.state.small_tab_selected === '') {
            return {
                label: 'Please select',
                value: ''
            };
        }

        const selectedTab = this.tabs.find((tab) => tab.key === this.state.small_tab_selected);

        if (!selectedTab) {
            return {
                label: 'Please select',
                value: ''
            };
        }

        return {
            label: selectedTab?.name,
            value: selectedTab?.key
        };
    }

    renderSmallSection() {
        switch(this.state.small_tab_selected) {
            case 'core-info':
                return <AdditionalInformation character={this.props.character} />
            case 'resistance':
                return <CharacterResistances character={this.props.character} />
            case 'reincarnation':
                return <CharacterReincarnation character={this.props.character} />
            case 'class-ranks':
                return <CharacterClassRanks character={this.props.character} />
            case 'elemental-atonement':
                return <CharacterElementalAtonement character={this.props.character} />
            default:
                return null;
        }
    }

    renderTabs() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'core-info'}>
                    <AdditionalInformation character={this.props.character} />
                </TabPanel>
                <TabPanel key={'resistance'}>
                    <CharacterResistances character={this.props.character} />
                </TabPanel>
                <TabPanel key={'reincarnation'}>
                    <CharacterReincarnation character={this.props.character} />
                </TabPanel>
                <TabPanel key={'class-ranks'}>
                    <CharacterClassRanks character={this.props.character} />
                </TabPanel>
                <TabPanel key={'elemental-atonement'}>
                    <CharacterElementalAtonement character={this.props.character} />
                </TabPanel>
            </Tabs>
        );
    }

    render() {

        console.log(this.state.view_port);

        if (this.state.view_port < 775) {
            return (
                <>
                    <div className="my-4">
                        <Select
                            onChange={this.setSelected.bind(this)}
                            options={this.sectionDropDownOptions()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.getSelectedValue()}
                        />
                    </div>

                    {this.renderSmallSection()}
                </>
            )
        }

        return this.renderTabs();
    }
}
