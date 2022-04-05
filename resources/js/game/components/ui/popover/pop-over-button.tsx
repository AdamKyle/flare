import React, {Fragment} from "react";
import {Popover, Transition} from '@headlessui/react'
import PopOverContainerProps from "../../../lib/ui/types/popover/pop-over-container-props";
import clsx from "clsx";
import PopOverButtonProps from "../../../lib/ui/types/popover/pop-over-button";

export default class PopOverButton extends React.Component<PopOverButtonProps, any> {

    constructor(props: PopOverButtonProps) {
        super(props);
    }

    render() {
        return (

            <div className="px-4">
                <Popover className="relative">
                    {({open}) => (
                        <>
                            <Popover.Button className='text-blue-500 dark:text-blue-300'>
                                <button
                                    type={'button'}
                                    onClick={this.props.on_click}
                                    disabled={this.props.disabled}
                                    className={clsx({
                                        'hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:bg-blue-600 dark:hover:text-white font-semibold py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-blue-400 dark:disabled:bg-blue-400': this.props.button_type === 'primary'
                                    }, {
                                        'hover:bg-green-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-green-600 dark:bg-green-700 text-white dark:hover:bg-green-600 dark:hover:text-white font-semibold py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-green-400 dark:disabled:bg-green-400': this.props.button_type === 'success'
                                    }, {
                                        'inline-flex justify-center hover:bg-red-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-red-600 dark:bg-red-700 text-white dark:hover:bg-red-600 dark:hover:text-white font-semibold py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-red-400 dark:disabled:bg-red-400': this.props.button_type === 'danger'
                                    })}
                                >{this.props.button_title}</button>
                            </Popover.Button>
                            <Transition
                                as={Fragment}
                                enter="transition ease-out duration-200"
                                enterFrom="opacity-0 translate-y-1"
                                enterTo="opacity-100 translate-y-0"
                                leave="transition ease-in duration-150"
                                leaveFrom="opacity-100 translate-y-0"
                                leaveTo="opacity-0 translate-y-1"
                            >
                                <Popover.Panel
                                    className={clsx('absolute z-50 w-screen px-4 mt-3 transform -translate-x-3/4', {
                                        'md:-translate-x-1/2 left-1/2 sm:px-0 lg:max-w-3xl': !this.props.make_small
                                    }, {
                                        'left-1/2 sm:px-0 lg:max-w-[350px]': this.props.make_small
                                    }, ' ') + this.props.additional_css}>
                                    <div
                                        className="overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                                        <div className="relative bg-white dark:bg-gray-700 p-7 font-thin">
                                            {this.props.children}
                                        </div>
                                    </div>
                                </Popover.Panel>
                            </Transition>
                        </>
                    )}
                </Popover>
            </div>
        )
    }

}
