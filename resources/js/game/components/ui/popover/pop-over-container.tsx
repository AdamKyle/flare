import React, {Fragment} from "react";
import {Popover, Transition} from '@headlessui/react'
import PopOverContainerProps from "../../../lib/ui/types/popover/pop-over-container-props";
import clsx from "clsx";

export default class PopOverContainer extends React.Component<PopOverContainerProps, any> {

    constructor(props: PopOverContainerProps) {
        super(props);
    }

    render() {
        return (

            <div className="px-4">
                <Popover className="relative">
                    {({open}) => (
                        <>
                            <Popover.Button className='text-blue-500 dark:text-blue-300'>
                                <i className={this.props.icon}></i> {this.props.icon_label}
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
                                    className={clsx('absolute z-50 w-screen px-4 mt-3 transform translate-x-[-60%]', {
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
