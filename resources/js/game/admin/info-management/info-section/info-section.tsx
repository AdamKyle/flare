import React from "react";
const ReactQuill = require('react-quill');
import 'react-quill/dist/quill.snow.css';
import BasicCard from "../../../components/ui/cards/basic-card";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";

export default class InfoSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            content: null,
            selected_live_wire_component: null,
            image_to_upload: null,
        }
    }

    componentDidMount() {
        this.setState({
            content: this.props.content.content,
            selected_live_wire_component: this.props.content.live_wire_component,
            image_to_upload: null,
        })
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
            label: 'Quest items',
            value: 'info.quest-items.quest-items-table',
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

    updateParentElement() {
        this.props.update_parent_element(this.props.index, {
            live_wire_component: this.state.selected_live_wire_component,
            content: this.state.content,
            content_image: this.state.image_to_upload,
        });
    }

    render() {
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

                {
                   this.props.add_section !== null ?
                       <div className='text-right'>
                           <PrimaryButton button_label={'Add Section'} on_click={this.props.add_section} additional_css={'mt-4'}/>
                       </div>
                   : null
                }
            </BasicCard>
        )
    }
}
