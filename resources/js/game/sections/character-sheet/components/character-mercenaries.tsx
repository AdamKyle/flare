import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import Table from "../../../components/ui/data-tables/table";
import {watchForDarkModeTableChange} from "../../../lib/game/dark-mode-watcher";
import clsx from "clsx";
import MercenaryInfoModal from "./modals/mercenary-info-modal";
import CharacterMercenariesProps from "../../../lib/game/character-sheet/types/mercenaries/character-mercenaries-props";
import CharacterMercenariesState from "../../../lib/game/character-sheet/types/mercenaries/character-mercenaries-state";

export default class CharacterMercenaries extends React.Component<CharacterMercenariesProps, CharacterMercenariesState> {

    constructor(props: CharacterMercenariesProps) {
        super(props);

        this.state = {
            loading: false,
            mercs: [],
            mercs_to_buy: [],
            merc_xp_buffs: [],
            merc_selected: null,
            buying_merc: false,
            error_message: null,
            success_message: null,
            reincarnate_error_message: null,
            reincarnate_success_message: null,
            reincarnating: false,
            buying_buff: false,
            dark_tables: false,
            show_merc_details: false,
            merc_for_show: null,
        }
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        let characterId: number|null = null;

        if (this.props.character === null) {
            return;
        }

        characterId = this.props.character.id;

        if (this.props.character.is_mercenary_unlocked) {
            this.setState({
                loading: true,
            }, () => {
                (new Ajax()).setRoute('mercenaries/list/' + characterId).doAjaxCall('get', (response: AxiosResponse) => {
                    this.setState({
                        mercs: response.data.merc_data,
                        mercs_to_buy: response.data.mercs_to_buy,
                        merc_xp_buffs: response.data.merc_xp_buffs,
                        loading: false,
                    })
                }, (error: AxiosError) => {
                    console.error(error);
                })
            })
        }
    }

    buildMerList() {
        if (this.state.mercs_to_buy === null) {
            return [{label: '', value: 0}];
        }

        return this.state.mercs_to_buy.map((merc: any) => {
            return {label: merc.name, value: merc.value};
        });
    }

    setMercenaryToBuy(data: any) {
        this.setState({
            merc_selected: data.value,
        });
    }

    defaultMerc(): {label: string, value: number}[] {

        if (this.state.merc_selected !== null) {
            const merc_selected: any = this.findMerc(this.state.merc_selected);

            if (merc_selected !== null) {
                return [{ label: merc_selected.name, value: merc_selected.value}];
            }
        }

        return [{label: 'Please Select', value: 0}];
    }

    findMerc(merc_selected: string): any {
        const foundMerc: any = this.state.mercs_to_buy.filter((merc: any) => {
            return merc.value === merc_selected;
        })

        if (foundMerc.length > 0) {
            return foundMerc[0];
        }

        return null;
    }

    closeSuccess() {
        this.setState({success_message: null});
    }

    closeError() {
        this.setState({error_message: null});
    }

    closeReincarnatedMessage() {
        this.setState({reincarnate_success_message: null})
    }

    closeDetailsModal() {
        return this.setState({
            show_merc_details: false,
            merc_for_show: null,
        });
    }

    manageDetailsModal(mercName: string) {
        const index = this.state.mercs.findIndex((merc: any) => {
            return merc.name === mercName
        });

        if (index != -1) {
            this.setState({
                merc_for_show: JSON.parse(JSON.stringify(this.state.mercs[index])),
                show_merc_details: true,
            });
        }
    }

    reincarnateMerc(mercId: number) {

        let characterId: number|null = null;

        if (this.props.character === null) {
            return;
        }

        characterId = this.props.character.id;

        this.setState({
            reincarnating: true,
            reincarnate_success_message: null,
            reincarnate_error_message: null,
        }, () => {
            (new Ajax()).setRoute('mercenaries/re-incarnate/'+characterId+'/' + mercId).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    mercs: response.data.merc_data,
                    mercs_to_buy: response.data.mercs_to_buy,
                    reincarnating: false,
                    reincarnate_success_message: response.data.message,
                }, () => this.closeDetailsModal());
            }, (error: AxiosError) => {
               if (typeof error.response !== 'undefined') {
                   const response = error.response;

                   this.setState({
                       reincarnating: false,
                       reincarnate_error_message: response.data.message,
                   });
               }
            });
        })
    }

    purchaseBuff(mercId: number, buffType: string) {
        let characterId: number|null = null;

        if (this.props.character === null) {
            return;
        }

        characterId = this.props.character.id;

        this.setState({
            success_message: null,
            error_message: null,
            buying_buff: true,
        }, () => {
            (new Ajax()).setRoute('mercenaries/purcahse-buff/'+characterId+'/' + mercId).setParameters({
                type: buffType
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    mercs: response.data.merc_data,
                    mercs_to_buy: response.data.mercs_to_buy,
                    buying_buff: false,
                    success_message: response.data.message,
                }, () => {
                    this.closeDetailsModal()
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({buying_buff: false, error_message: response.data.message});
                }
            });
        })
    }

    buyMerc() {
        let characterId: number|null = null;

        if (this.props.character === null) {
            return;
        }

        characterId = this.props.character.id;

        this.setState({
            success_message: null,
            error_message: null,
            buying_merc: true,
        }, () => {
            (new Ajax()).setRoute('mercenaries/buy/' + characterId).setParameters({
                type: this.state.merc_selected
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    mercs: response.data.merc_data,
                    mercs_to_buy: response.data.mercs_to_buy,
                    buying_merc: false,
                    success_message: response.data.message,
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({buying_merc: false, error_message: response.data.message});
                }
            });
        })
    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: any) => row.name,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    <button className='text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-500' onClick={() => this.manageDetailsModal(row.name)}>
                        {row.name}
                    </button>
                </span>
            },
            {
                name: 'Xp',
                selector: (row: any) => row.current_xp,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {row.current_xp}/{row.xp_required}
                </span>
            },
            {
                name: 'Level',
                selector: (row: any) => row.level,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {row.level}/{row.max_level}
                </span>
            },
            {
                name: 'Bonus',
                selector: (row: any) => row.bonus,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {(row.bonus * 100).toFixed(0)}%
                </span>
            },
            {
                name: 'XP Buff',
                selector: (row: any) => row.xp_buff,
                sortable: true,
                cell: (row: any) => <span
                    key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {(row.xp_buff * 100).toFixed(0)}%
                </span>
            },
        ];
    }

    render() {

        if (this.props.character === null) {
            return;
        }

        if (!this.props.character.is_mercenary_unlocked) {
            return (
                <div className='text-center text-red-500 dark:text-red-400'>
                    <p>You must complete a quest to unlock this feature. Head down to <a href='/information/planes' target='_blank' >Labyrinth <i
                        className="fas fa-external-link-alt"></i></a></p> and complete the <a href='/information/quests' target='_blank'>Quest: <i
                    className="fas fa-external-link-alt"></i></a> "The story of the children".
                </div>
            );
        }

        if (this.state.loading) {
            return <ComponentLoading />
        }

        return (
            <div>

                {
                    this.state.reincarnate_success_message !== null ?
                        <SuccessAlert additional_css={'mb-4'} close_alert={this.closeReincarnatedMessage.bind(this)}>
                            {this.state.reincarnate_success_message}
                        </SuccessAlert>
                    : null
                }

                <Table columns={this.buildColumns()} data={this.state.mercs} dark_table={this.state.dark_tables} />

                <div className={clsx('flex justify-center mt-4', {
                    'hidden': this.state.mercs_to_buy.length === 0,
                })}>
                    <div>
                        <Select
                            onChange={this.setMercenaryToBuy.bind(this)}
                            options={this.buildMerList()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.defaultMerc()}
                        />
                    </div>
                    <div>
                        <PrimaryButton button_label={'Purchase'} on_click={this.buyMerc.bind(this)} additional_css={'ml-2'} />
                    </div>
                    <div>
                        <a href='/information/mercenary' target='_blank' className='ml-2 relative top-[5px]'>Help <i
                            className="fas fa-external-link-alt"></i></a>
                    </div>
                </div>
                <p className={clsx('my-2 text-sm text-center', {
                    'hidden': this.state.mercs_to_buy.length === 0,
                })}>Each Mercenary costs: 10,000,000 Gold</p>
                <div className='my-2 w-1/2 ml-auto mr-auto'>

                    {
                        this.state.buying_merc ?
                            <LoadingProgressBar />
                        : null
                    }

                    {
                        this.state.success_message !== null ?
                            <SuccessAlert close_alert={this.closeSuccess.bind(this)}>{this.state.success_message}</SuccessAlert>
                            : null
                    }

                    {
                        this.state.error_message !== null ?
                            <DangerAlert close_alert={this.closeError.bind(this)}>{this.state.error_message}</DangerAlert>
                        : null
                    }
                </div>

                {
                    this.state.show_merc_details ?
                        <MercenaryInfoModal mercenary={this.state.merc_for_show}
                                            is_open={true}
                                            handle_close={this.closeDetailsModal.bind(this)}
                                            character={this.props.character}
                                            reincarnating={this.state.reincarnating}
                                            buying_buff={this.state.buying_buff}
                                            error_message={this.state.reincarnate_error_message}
                                            reincarnate={this.reincarnateMerc.bind(this)}
                                            xp_buffs={this.state.merc_xp_buffs}
                                            purchase_buff={this.purchaseBuff.bind(this)}
                        />
                    : null
                }
            </div>
        );
    }
}
