import React, {Fragment} from "react";
const ReactQuill = require('react-quill');
import 'react-quill/dist/quill.snow.css';
import BasicCard from "../../../components/ui/cards/basic-card";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {isEqual} from "lodash";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import SuccessButton from "../../../components/ui/buttons/success-button";
import OrangeButton from "../../../components/ui/buttons/orange-button";

export default class InfoSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            content: '',
            selected_live_wire_component: null,
            image_to_upload: null,
            order: '',
            loading: true,
        }
    }

    componentDidMount() {

        const self = this;

        setTimeout(function(){
            self.setState({
                content: self.props.content.content,
                selected_live_wire_component: self.props.content.live_wire_component,
                image_to_upload: null,
                order: self.props.content.order,
                loading: false,
            })
        }, 500);
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<any>, snapshot?: any) {
        if (!isEqual(this.props.content.content, prevProps.content.content)) {
            this.setState({
                content: this.props.content.content,
            });
        }
    }

    setValue(data: any) {
        this.setState({
            content: data,
        }, () => {
            this.updateParentElement();
        })
    }

    setLivewireComponent(data: any) {
        this.setState({
            selected_live_wire_component: data.value !== '' ? data.value : null,
        }, () => {
            this.updateParentElement();
        })
    }

    setOrder(e: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            order: e.target.value
        }, () => {
            this.updateParentElement();
        })
    }

    updateParentElement() {
        this.props.update_parent_element(this.props.index, {
            live_wire_component: this.state.selected_live_wire_component,
            content: this.state.content,
            content_image_path: this.state.image_to_upload,
            order: this.state.order,
        });
    }

    removeSection() {
        this.props.remove_section(this.props.index);
    }

    buildOptions() {
        return [{
            label: 'Items',
            value: 'admin.items.items-table',
        }, {
            label: 'Races',
            value: 'admin.races.races-table',
        },{
            label: 'Classes',
            value: 'admin.classes.classes-table',
        },{
            label: 'Monsters',
            value: 'admin.monsters.monsters-table',
        },{
            label: 'Celestials',
            value: 'admin.monsters.celestials-table',
        },{
            label: 'Quest items',
            value: 'info.quest-items.quest-items-table',
        },{
            label: 'Crafting Books',
            value: 'info.quest-items.crafting-books-table'
        },{
            label: 'Craftable Items',
            value: 'info.items.craftable-items-table',
        },{
            label: 'Craftable Trinkets',
            value: 'info.items.craftable-trinkets',
        },{
            label: 'Enchantments',
            value: 'admin.affixes.affixes-table'
        },{
            label: 'Alchemy Items',
            value: 'info.alchemy-items.alchemy-items-table',
        },{
            label: 'Skills',
            value: 'admin.skills.skills-table',
        },{
            label: 'Class Skills',
            value: 'info.skills.class-skills',
        },{
            label: 'Maps',
            value: 'admin.maps.maps-table',
        },{
            label: 'NPCs',
            value: 'admin.npcs.npc-table',
        },{
            label: 'Kingdom Passive Skills',
            value: 'admin.passive-skills.passive-skill-table',
        }];
    }

    setFileForUpload(event: React.ChangeEvent<HTMLInputElement>) {
        if (event.target.files !== null ) {
            this.setState({
                image_to_upload: event.target.files[0],
            }, () => {
                this.updateParentElement();
            })
        }
    }

    defaultSelectedAction() {
        if (this.state.selected_live_wire_component !== null) {
            return this.buildOptions().filter((option: any) => option.value === this.state.selected_live_wire_component)
        }

        return [{
            label: 'Please Select',
            value: '',
        }];
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />
        }

        return (
            <BasicCard additionalClasses={'mb-4'}>
                {
                    this.props.index !== 0 ?
                        <div className='mb-5'>
                            <button type='button' onClick={this.removeSection.bind(this)} className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'>
                                <i className="fas fa-times-circle"></i>
                            </button>
                        </div>
                    : null
                }

                <ReactQuill theme="snow" value={this.state.content} onChange={this.setValue.bind(this)}/>

                <div className="my-5">
                    <label className="label block mb-2">Order</label>
                    <input type="number" className="form-control" onChange={this.setOrder.bind(this)} value={this.state.order} />
                </div>

                <div className="my-5">
                    <input type="file" className="form-control" onChange={this.setFileForUpload.bind(this)} />
                </div>

                <Select
                    onChange={this.setLivewireComponent.bind(this)}
                    options={this.buildOptions()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.defaultSelectedAction()}
                />

                <div className='flex mt-4 justify-end'>

                    {
                        this.props.sections_length !== 1 && this.props.add_section === null ?
                            <div className='float-right'>
                                <OrangeButton button_label={'Update Section'} on_click={() => this.props.update_section(this.props.index)} additional_css={'mr-4'}/>
                            </div>
                        : null
                    }

                    {
                        this.props.sections_length === 1 && this.props.index === 0 ?
                            <div className='float-right'>
                                <SuccessButton button_label={'Save and Finish'} on_click={this.props.save_and_finish} additional_css={'mr-4'}/>
                            </div>
                        : null
                    }

                    {
                        this.props.index !== 0 && this.props.add_section !== null ?
                            <div className='float-right'>
                                <SuccessButton button_label={'Save and Finish'} on_click={this.props.save_and_finish}
                                               additional_css={'mr-4'}/>
                            </div>
                        : null
                    }

                    {
                       this.props.add_section !== null ?
                           <div className='float-right'>
                               <PrimaryButton button_label={'Add Section'} on_click={this.props.add_section} additional_css={'mr-4'}/>
                           </div>
                       : null
                    }
                </div>
            </BasicCard>
        )
    }
}
