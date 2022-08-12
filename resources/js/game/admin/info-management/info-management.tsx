import React, {Fragment} from "react";
import InfoSection from "./info-section/info-section";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ManualProgressBar from "../../components/ui/progress-bars/manual-progress-bar";
import SuccessAlert from "../../components/ui/alerts/simple-alerts/success-alert";
import ComponentLoading from "../../components/ui/loading/component-loading";
import SuccessButton from "../../components/ui/buttons/success-button";
import DangerButton from "../../components/ui/buttons/danger-button";
import {cloneDeep, isEqual} from "lodash";

export default class InfoManagement extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            info_sections: [],
            page_name: '',
            error_message: null,
            loading: false,
            posting: false,
            posting_index: 0,
            success_message: null,
        }
    }

    componentDidMount() {
        if (this.props.info_page_id !== 0) {
            this.setState({
                loading: true,
            }, () => {
                (new Ajax()).setRoute('admin/info-section/page').setParameters({
                    page_id: this.props.info_page_id,
                }).doAjaxCall('get', (result: AxiosResponse) => {
                    this.setState({
                        page_name: result.data.page_name,
                        info_sections: result.data.page_sections,
                        loading: false,
                    })
                }, (error: AxiosError) => {});
            });
        } else {
            this.addSection();
        }
    }

    formatAndSendData(section: any, redirect: boolean) {

        const form = new FormData();

        form.append('content', section.content);
        form.append('live_wire_component', section.live_wire_component)
        form.append('page_name', this.state.page_name);
        form.append('order', section.order);

        if (section.content_image !== null) {
            form.append('content_image', section.content_image);
        }

        if (this.props.info_page_id !== 0) {
            form.append('page_id', this.props.info_page_id);
        }

        this.postForm(form, redirect);

    }

    postForm(form: FormData, redirect: boolean) {

        this.setState({
            posting: true,
        }, () => {
            this.post(form, redirect);
        });
    }

    delete() {
        (new Ajax()).setRoute('admin/info-section/delete-page')
            .setParameters({
                page_id: this.props.info_page_id
            })
            .doAjaxCall('post', (result: AxiosResponse) => {
                location.href = '/admin/information-management';
            }, (error: AxiosError) => {})
    }

    post(form: FormData, redirect: boolean) {
        let url = 'admin/info-section/store-page';

        if (this.props.info_page_id !== 0) {
            url = 'admin/info-section/update-page';
        }

        (new Ajax()).setRoute(url)
            .setParameters(form)
            .doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    posting: false,
                });

                if (redirect) {
                    window.location.href = '/admin/information-management/page/' + result.data.pageId;
                }
            }, (error: AxiosError) => {
                this.setState({
                    posting: false,
                });

                console.error(error);
            });
    }

    deleteSection(order: number) {
        (new Ajax()).setRoute('admin/info-section/delete-section/' + this.props.info_page_id)
            .setParameters({
                order: order
            })
            .doAjaxCall('post', (result: AxiosResponse) => {
                const sections = result.data.sections;
                const stateSections = JSON.parse(JSON.stringify(this.state.info_sections));

                stateSections.forEach((stateSection: any, index: number) => {
                    if (!isEqual(stateSection.content, sections[index].content)) {
                        stateSections[index] = sections[index];
                    }

                    stateSections[index].order = parseInt(sections[index].order);
                });

                this.setState({
                    info_sections: stateSections,
                })
            }, (error: AxiosError) => {

            });
    }

    setInfoSections(index: number, content: any) {
        const sections = JSON.parse(JSON.stringify(this.state.info_sections));

        sections[index] = content;

        this.setState({
            info_sections: sections,
        });
    }

    addSection() {
        const infoSections = cloneDeep(this.state.info_sections);
        const order        = 1;

        infoSections.push({
            live_wire_component: null,
            content: null,
            content_image: null,
            is_new_section: true,
            order: order,
        });

        if (infoSections.length > 1) {
            const sectionToPublish = infoSections[infoSections.length - 2];

            infoSections[infoSections.length - 1].order = parseInt(sectionToPublish.order)  + 1

            this.formatAndSendData(sectionToPublish, false);
        }

        this.setState({
            info_sections: infoSections,
        });
    }

    saveAndFinish() {
        const infoSections  = JSON.parse(JSON.stringify(this.state.info_sections));
        const sectionToSave = infoSections[infoSections.length - 1];

        this.formatAndSendData(sectionToSave, true);
    }

    removeSection(index: number) {
        if (index <= 0) {
            return;
        }

        const infoSections = JSON.parse(JSON.stringify(this.state.info_sections));

        if (this.props.info_page_id !== 0 && typeof infoSections[index].is_new_section === 'undefined') {
            const section = infoSections[index];

            infoSections.splice(index, 1);

            this.deleteSection(section.order);
        } else {
            infoSections.splice(index, 1);
        }

        this.setState({
            info_sections: infoSections,
        });
    }

    setPageName(event: React.ChangeEvent<HTMLInputElement>) {
        this.setState({
            page_name: event.target.value,
        });
    }

    goHome() {
        return location.href = '/';
    }

    goBack() {

        if (this.props.info_page_id !== 0) {
            return location.href = '/admin/information-management/page/' + this.props.info_page_id;
        }

        return location.href= '/admin/information-management';
    }

    renderContentSections() {
        return this.state.info_sections.map((infoSection: any, index: number, elements: any[]) => {

            return <InfoSection index={index}
                                sections_length={this.state.info_sections.length}
                                content={infoSection}
                                update_parent_element={this.setInfoSections.bind(this)}
                                remove_section={this.removeSection.bind(this)}
                                add_section={index === (elements.length - 1) ? this.addSection.bind(this) : null}
                                save_and_finish={this.saveAndFinish.bind(this)}
            />
        });
    }

    render() {
        if (this.state.loading) {
            return (
                <div className='py-5'>
                    <ComponentLoading />
                </div>
            );
        }

        return  (
            <Fragment>
                <div className='grid grid-cols-2 gap-4 mb-5'>
                    <h3 className='text-left'>Content</h3>
                    <div className='text-right'>
                        <SuccessButton button_label={'Home Section'} on_click={this.goHome.bind(this)} additional_css={'mr-2'} />
                        <SuccessButton button_label={'Back'} on_click={this.goBack.bind(this)} additional_css={'mr-2'} />
                        {
                            this.props.info_page_id !== 0 ?
                                <DangerButton button_label={'Delete Page'} on_click={this.delete.bind(this)} />
                            : null
                        }
                    </div>
                </div>

                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'my-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }

                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css={'my-4'}>
                            {this.state.success_message}
                        </SuccessAlert>
                        : null
                }

                <div className="my-5">
                    <label className="label block mb-2" >Page Name</label>
                    <input type="text" className="form-control" onChange={this.setPageName.bind(this)} value={this.state.page_name} disabled={this.props.info_page_id !== 0}/>
                </div>

                {
                    this.state.posting ?
                        <div className='mt-4 mb-4'>
                            <ManualProgressBar label={'Posting #: ' + this.state.posting_index}
                                               secondary_label={this.state.posting_index + '/' + this.state.info_sections.length + ' sections posted'}
                                               percentage_left={this.state.posting_index / (this.state.info_sections.length - 1)}
                                               show_loading_icon={true}
                            />
                        </div>
                    : null
                }

                {
                    this.renderContentSections()
                }
            </Fragment>
        )
    }
}
