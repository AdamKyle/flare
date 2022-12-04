import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";
import CharacterClassRanks from "../character-class-ranks";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ClassSpecialtiesEquippedProps
    from "../../../../lib/game/character-sheet/types/modal/class-specialties-equipped-props";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ClassSpecialtiesState
    from "../../../../lib/game/character-sheet/types/class-ranks/types/class-specialties-state";
import ClassSpecialtiesType from "../../../../lib/game/character-sheet/types/class-ranks/class-specialties-type";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import {
    watchForDarkModeClassRankChange,
    watchForDarkModeClassSpecialtyChange
} from "../../../../lib/game/dark-mode-watcher";
import Table from "../../../../components/ui/data-tables/table";

export default class CharacterClassSpecialtiesModal extends React.Component<ClassSpecialtiesEquippedProps, ClassSpecialtiesState> {

    private tabs: { key: string, name: string }[];

    constructor(props: ClassSpecialtiesEquippedProps) {
        super(props);

        this.state = {
            loading: true,
            class_specialties: [],
            specialties_equipped: [],
            dark_tables: false,
            special_selected: null,
        }

        this.tabs = [{
            key: 'class-specialties',
            name: 'Class Specialties'
        }, {
            key: 'equipped-specialities',
            name: 'Equipped Specialties',
        }]
    }

    componentDidMount() {

        watchForDarkModeClassSpecialtyChange(this);

        if (this.props.character === null || this.props.class_rank === null) {
            return;
        }

        (new Ajax()).setRoute('class-ranks/'+this.props.character.id+'/specials/' + this.props.class_rank.id)
                    .doAjaxCall('get', (response: AxiosResponse) => {
                        this.setState({
                            loading: false,
                            class_specialties: response.data.class_specialties,
                            specialties_equipped: response.data.specials_equipped,
                        });
                    }, (error: AxiosError) => {
                        console.error(error);
                    })
    }

    equipSpecial(specialId: number) {

    }

    classSpecialtiesTable() {

        return [
            {
                name: 'Name',
                selector: (row: ClassSpecialtiesType ) => row.name,
                cell: (row: ClassSpecialtiesType) => <Fragment>
                    <button className='hover:underline text-blue-500 dark:text-blue-400' onClick={() => this.manageViewSpecialty(row)}>{row.name}</button>
                </Fragment>
            },
            {
                name: 'Class Rank Required',
                selector: (row: ClassSpecialtiesType) => row.requires_class_rank_level,
            },
            {
                name: 'Actions',
                selector: (row: ClassSpecialtiesType) => row.id,
                cell: (row: ClassSpecialtiesType) => <Fragment>
                    <PrimaryButton button_label={'Equip'} on_click={() => this.equipSpecial(row.id)} disabled={this.isEquipButtonDisabled(row.requires_class_rank_level)} />
                </Fragment>
            },
        ];
    }

    isEquipButtonDisabled(requiredLevel: number): boolean {
        if (this.props.class_rank === null) {
            return true;
        }

        return requiredLevel !== this.props.class_rank.level;
    }

    manageViewSpecialty(specialty: ClassSpecialtiesType | null) {
        this.setState({
            special_selected: specialty
        });
    }

    renderSpecialty() {
        if (this.state.special_selected === null) {
            return;
        }

        return (
            <div>
                <div className='text-right cursor-pointer text-red-500 position top-[-10px]'>
                    <button onClick={() => this.manageViewSpecialty(null)}><i className="fas fa-minus-circle"></i></button>
                </div>
                <div className='my-4'>
                    <h3 className='text-sky-700 dark:text-sky-500 font-bold my-4'>{this.state.special_selected.name}</h3>
                    <p className='my-4'>
                        {this.state.special_selected.description}
                    </p>
                    <div className='grid lg:grid-cols-2 gap-2'>
                        <div>
                            <h3>Damage Information</h3>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <dl>
                                <dt>Damage Amount:</dt>
                                <dd>{formatNumber(this.state.special_selected.specialty_damage)}</dd>
                                <dt>Damage Increase per level:</dt>
                                <dd>{formatNumber(this.state.special_selected.increase_specialty_damage_per_level)}</dd>
                                <dt>% Of Damage Stat Used:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.specialty_damage_uses_damage_stat_amount)}%</dd>
                            </dl>
                        </div>
                        <div className='lg:hidden block border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <div>
                            <h3>Modifier Information</h3>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <dl>
                                <dt>Base Damage Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.base_damage_mod)}%</dd>
                                <dt>Base AC Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.base_ac_mod)}%</dd>
                                <dt>Base Healing Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.base_healing_mod)}%</dd>
                                <dt>Base Spell Damage Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.base_spell_damage_mod)}%</dd>
                                <dt>Base Health Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.health_mod)}%</dd>
                                <dt>Base Damage Stat Modifier:</dt>
                                <dd>{this.renderPercent(this.state.special_selected.base_damage_stat_increase)}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        )
    }

    renderPercent(value: number|null) {
        if (value === null) {
            return 0;
        }

        return (value * 100).toFixed(0);
    }

    renderSpecialties() {
        return (
            <Fragment>
                <p className='my-4 text-sm'>
                    Each class has its own specialties that unlock at specific Clas Ranks for a class.
                    As you level the class, through killing monsters, you will slowly unlock the
                    specialities for the selected class. A player may only have three specialities equipped
                    and can only have one damage speciality equipped. You may mix and match across classes
                    to create your own unique build.
                </p>
                <Tabs tabs={this.tabs}>
                    <TabPanel key={'class-specialties'}>
                        <Table
                            data={this.state.class_specialties}
                            columns={this.classSpecialtiesTable()}
                            dark_table={this.state.dark_tables}
                        />
                    </TabPanel>

                    <TabPanel key={'equipped-specialties'}>
                        Your Stuff
                    </TabPanel>
                </Tabs>
            </Fragment>
        )
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      medium_modal={true}
            >
                {
                    this.state.loading ?
                        <div className='p-10'>
                            <ComponentLoading />
                        </div>
                    :
                        this.state.special_selected !== null ?
                            this.renderSpecialty()
                        :
                            this.renderSpecialties()
                }
            </Dialogue>
        );
    }
}
