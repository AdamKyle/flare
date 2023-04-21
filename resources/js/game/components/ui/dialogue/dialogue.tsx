import React, {Fragment} from "react";
import { Dialog, Transition } from '@headlessui/react'
import DialogueTypes from "../../../lib/ui/types/dialogue/dialogue-types";
import DangerButton from "../buttons/danger-button";
import PrimaryButton from "../buttons/primary-button";
import clsx from "clsx";

export default class Dialogue extends React.Component<DialogueTypes, any> {
    constructor(props: DialogueTypes) {
        super(props);
    }

    closeModal() {
        if (typeof this.props.handle_close !== 'undefined') {
            this.props.handle_close();
        }
    }

    emptyClose(){}

    render() {
        return (
            <Transition appear show={this.props.is_open} as={Fragment}>
                <Dialog
                    as="div"
                    className={"absolute inset-0 z-50"}
                    onClose={this.emptyClose.bind(this)}
                >
                    <Dialog.Overlay className="fixed inset-0 bg-black opacity-30" />

                    <div className="min-h-screen px-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0"
                            enterTo="opacity-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100"
                            leaveTo="opacity-0"
                        >
                            <Dialog.Overlay className="fixed inset-0" />
                        </Transition.Child>

                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <div className="fixed inset-0 flex items-center justify-center p-4">
                                <div className="flex min-h-full min-w-full items-center justify-center">
                                <div className={clsx("inline-block w-full p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-700 drop-shadow-2xl rounded-md", {'max-w-7xl': this.props.large_modal && !this.props.medium_modal}, {'max-w-5xl': this.props.medium_modal}, {'max-w-3xl': !this.props.large_modal && !this.props.medium_modal})}>
                                    <Dialog.Title
                                        as="span"
                                        className="flex items-center text-lg font-medium leading-6 text-gray-700 dark:text-gray-500 relative mb-5 text-[16px] lg:text-xl"
                                    >
                                        {this.props.title}
                                        <button className='flex items-center absolute right-[20px] cursor-pointer hover:text-gray-800 dark:hover:text-gray-600 top-[5px]' onClick={this.closeModal.bind(this)} disabled={this.props.primary_button_disabled}><i className="fas fa-times "></i></button>
                                    </Dialog.Title>
                                    <div className="mt-2">
                                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                        {this.props.children}
                                    </div>
                                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                    <div className="mt-4">

                                        {
                                            typeof this.props.handle_close !== 'undefined' ?
                                                <DangerButton button_label={'Cancel'} on_click={this.closeModal.bind(this)} disabled={this.props.primary_button_disabled} />
                                            : null
                                        }
                                        {
                                            typeof this.props.secondary_actions !== 'undefined' && this.props.secondary_actions !== null ?
                                                <PrimaryButton additional_css={'float-right'} button_label={this.props.secondary_actions.secondary_button_label} on_click={this.props.secondary_actions.handle_action} disabled={this.props.secondary_actions.secondary_button_disabled}/>
                                            : null
                                        }
                                        {
                                            typeof this.props.tertiary_actions !== 'undefined' ?
                                                <PrimaryButton additional_css={'mr-2 float-right'} button_label={this.props.tertiary_actions.tertiary_button_label} on_click={this.props.tertiary_actions.handle_action} disabled={this.props.tertiary_actions.tertiary_button_disabled} />
                                            : null
                                        }
                                    </div>
                                </div>
                                </div>
                            </div>
                        </Transition.Child>
                    </div>
                </Dialog>
            </Transition>
        );
    }
}
