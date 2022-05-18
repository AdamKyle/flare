import React, {Fragment} from "react";
import InfoSection from "./info-section/info-section";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ManualProgressBar from "../../components/ui/progress-bars/manual-progress-bar";
import SuccessAlert from "../../components/ui/alerts/simple-alerts/success-alert";
import ComponentLoading from "../../components/ui/loading/component-loading";
import BasicCard from "../../components/ui/cards/basic-card";
import SuccessButton from "../../components/ui/buttons/success-button";
import DangerButton from "../../components/ui/buttons/danger-button";

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

    createPage() {
        if (this.state.page_name === '') {
            return this.setState({
                error_message: 'Page name needed.'
            });
        }

        if (this.state.info_sections.length === 0) {
            return this.setState({
                error_message: 'Need at least one info section.'
            });
        }

        this.setState({
            error_message: null,
        }, () => {
            this.formatAndSendData();
        })
    }

    formatAndSendData() {
        const sections = this.state.info_sections.map((section: any, index: number, elements: any[]) => {

            const form = new FormData();

            form.append('content', section.content);
            form.append('content_image', section.content_image);
            form.append('live_wire_component', section.live_wire_component)
            form.append('page_name', this.state.page_name);
            form.append('page_id', this.props.info_page_id);

            if (index === elements.length - 1) {
                form.append('final_section', 'true');
            } else {
                form.append('final_section', 'false');
            }

            if (this.props.info_page_id !== 0) {
                form.append('display_order', (index + 1).toString())
            } else {
                form.append('order', (index + 1).toString())
            }

            if (typeof section.delete !== 'undefined') {
                form.append('delete', 'true');
            }

            return {
                index: index + 1,
                form_contents: form
            };
        });

        this.postForms(sections);

    }

    postForms(sections: any[]) {
        window.scrollTo({ top: 0, behavior: 'smooth' });

        this.setState({
            posting: true,
        }, () => {
            sections.forEach((section: any, index: number) => {
                this.post(section, sections.length, index);
            });
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

    post(section: any, length: number, index: number) {
        let url = 'admin/info-section/store-page';

        if (this.props.info_page_id !== 0) {
            url = 'admin/info-section/update-page';
        }

        (new Ajax()).setRoute(url)
            .setParameters(section.form_contents)
            .doAjaxCall('post', (result: AxiosResponse) => {
                if (length - 1 === index) {
                    let sections = this.state.info_sections;

                    if (typeof result.data.page !== 'undefined') {
                        sections = result.data.page.page_sections;
                    }

                    return this.setState({
                        posting: false,
                        posting_index: 0,
                        success_message: result.data.message,
                        info_sections: sections,
                    });
                } else {
                    this.setState({
                        posting_index: this.state.posting_index + 1,
                    });
                }
            }, (error: AxiosError) => {

            });
    }

    setInfoSections(index: number, content: any) {
        if (this.state.info_sections.length === 0) {
            const sections = [];

            sections.push(content);

            this.setState({
                info_sections: sections
            });
        } else {
            const infoSections = this.state.info_sections;

            if (typeof infoSections[index] !== 'undefined') {
                infoSections[index] = content;
            } else {
                infoSections.push(content);
            }

            this.setState({
                info_sections: infoSections
            });
        }
    }

    addSection() {
        const infoSections = this.state.info_sections

        infoSections.push({
            live_wire_component: null,
            content: null,
            content_image: null,
            is_new_section: true,
        })

        this.setState({
            info_sections: infoSections,
        });
    }

    removeSection(index: number) {
        if (index <= 0) {
            return;
        }

        const infoSections = this.state.info_sections;

        if (this.props.info_page_id !== 0 && typeof infoSections[index].is_new_section === 'undefined') {
            infoSections[index]['delete'] = 'true';
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
            if (typeof infoSection.delete !== 'undefined') {
                return (
                    <BasicCard additionalClasses={'text-red-500 text-center mb-4'}>
                        This section is slated to be deleted.
                    </BasicCard>
                );
            }

            return <InfoSection index={index}
                                content={infoSection}
                                update_parent_element={this.setInfoSections.bind(this)}
                                remove_section={this.removeSection.bind(this)}
                                add_section={index === (elements.length - 1) ? this.addSection.bind(this) : null}
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

                <PrimaryButton button_label={this.props.info_page_id !== 0 ? 'Update Page' : 'Create Page'} on_click={this.createPage.bind(this)} />
            </Fragment>
        )
    }
}
