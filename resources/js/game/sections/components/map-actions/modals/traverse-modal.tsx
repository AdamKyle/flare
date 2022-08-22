import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";

export default class TraverseModal extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            game_maps: [],
            is_traversing: false,
            error_message: null,
            traverse_is_same_map: true,
            map: 0,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/traverse-maps').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                game_maps: result.data,
                loading: false,
            }, () => {
                this.disableTraverseForSameMap();
            })
        }, (error: AxiosError) => {
            if (typeof error.response !== 'undefined') {
                const response = error.response;

                this.setState({
                    loading: false,
                    error_message: response.data.message,
                });
            }
        });
    }

    setMap(data: any) {
        this.setState({
            map: data.value
        }, () => {
            this.disableTraverseForSameMap()
        })
    }

    buildTraverseOptions(): {value: number, label: string}[]|[] {
        if (this.state.game_maps.length > 0) {
            return this.state.game_maps.map((game_map: any) => {
                return {label: game_map.name, value: game_map.id}
            });
        }

        return [];
    }

    getDefaultValue() {
        const playerMap = this.state.game_maps.filter((map: any) => map.id === this.props.map_id)[0];

        if (this.state.map  === 0) {
            return {label: playerMap.name, value: playerMap.id}
        }

        const map = this.state.game_maps.filter((map: any) => map.id === this.state.map)[0];

        return {
            label: map.name,
            value: map.id
        }
    }

    disableTraverseForSameMap() {

        if (this.state.map === 0) {
            return;
        }

        this.setState({
            traverse_is_same_map: this.state.map === this.props.map_id,
        });
    }

    traverse() {
        this.setState({
            is_traversing: true,
        });

        (new Ajax()).setRoute('map/traverse/' + this.props.character_id).setParameters({
            map_id: this.state.map,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                is_traversing: false,
            });

            this.props.handle_close();
        }, (error: AxiosError) => {
            if (typeof error.response !== 'undefined') {
                const response = error.response;

                this.setState({
                    is_traversing: false,
                    error_message: response.data.message,
                });
            }
        });
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Traverse'}
                      primary_button_disabled={this.state.is_traversing}
                      secondary_actions={{
                          handle_action: this.traverse.bind(this),
                          secondary_button_disabled: this.state.is_traversing || this.state.loading || this.state.traverse_is_same_map,
                          secondary_button_label: 'Traverse',
                      }}
            >
                {
                    this.state.loading ?
                        <div className='p-10'>
                            <ComponentLoading />
                        </div>
                    :
                        <Fragment>
                            <p className='mb-4'>
                                Welcome to traverse. Every plane but Surface requires a quest item to access, you can gain these items by
                                switching to the quest tab in the game area and completing quests, some items drop off regular creatures,
                                some require quest chains to be completed.
                            </p>
                            <p className='mb-4'>
                                Some planes of existence like Shadow Planes, make character attacks weaker, while others like Hell and Purgatory will make
                                your character over all, weaker. To offset this, there is <a href='/information/gear-progression' target='_blank'>Gear Progression <i
                                className="fas fa-external-link-alt"></i></a> which if followed does help make these areas easier to farm valuable currencies and XP in.
                            </p>
                            <div className='w-2/3'>
                                {
                                    this.state.is_traversing ?
                                        <span className='text-orange-700 dark:text-orange-400'>Traversing. One moment ...</span>
                                    :
                                        <Select
                                            onChange={this.setMap.bind(this)}
                                            options={this.buildTraverseOptions()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.getDefaultValue()}
                                        />
                                }
                            </div>

                            {
                                this.state.error_message !== null ?
                                    <p className='mt-4 mb-4 text-red-500 dark:text-red-400'>{this.state.error_message}</p>
                                : null
                            }

                            {
                                this.state.is_traversing ?
                                    <LoadingProgressBar />
                                : null
                            }
                        </Fragment>
                }

            </Dialogue>
        )
    }
}
