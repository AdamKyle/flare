import React from 'react'
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react'
import clsx from 'clsx'
import TabsProps from "./types/tabs-props";
import TabsState from "./types/tabs-state";

export default class Tabs extends React.Component<TabsProps, TabsState> {
    constructor(props: TabsProps) {
        super(props)
        this.state = {
            selectedIndex: 0,
        }
    }

    handleChange = (index: number) => {
        this.setState({ selectedIndex: index })
        const { onChange } = this.props
        if (onChange) onChange(index)
    }

    render() {
        const { tabs, icons } = this.props
        const { selectedIndex } = this.state

        return (
            <div className="flex h-screen w-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-50">
                <div className="hidden md:flex flex-col w-64 border-r border-gray-200 dark:border-gray-700 top-0">
                    <TabGroup selectedIndex={selectedIndex} onChange={this.handleChange}>
                        <TabList className="flex flex-col p-4 sticky">
                            {tabs.map((tab, index) => (
                                <Tab
                                    key={tab}
                                    className={clsx(
                                        'flex items-center gap-4 rounded-lg py-3 px-4 text-base font-semibold',
                                        index === selectedIndex
                                            ? 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200'
                                            : 'text-gray-900 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors'
                                    )}
                                    aria-selected={index === selectedIndex}
                                    aria-controls={`panel-${index}`}
                                    id={`tab-${index}`}
                                >
                                    <i className={`${icons[index]} text-2xl`} />
                                    <span className="hidden md:inline">{tab}</span>
                                </Tab>
                            ))}
                        </TabList>
                    </TabGroup>
                </div>
                <div className="w-full flex-1 flex flex-col">
                    <TabGroup selectedIndex={selectedIndex} onChange={this.handleChange}>
                        <TabList className="md:hidden flex gap-6 px-4 py-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-gray-50 dark:bg-gray-900">
                            {tabs.map((tab, index) => (
                                <Tab
                                    key={index}
                                    className={clsx(
                                        'flex items-center gap-4 rounded-lg py-3 px-4 text-base font-semibold',
                                        index === selectedIndex
                                            ? 'text-gray-900 dark:text-gray-200'
                                            : 'text-gray-900 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors'
                                    )}
                                    aria-selected={index === selectedIndex}
                                    aria-controls={`panel-${index}`}
                                    id={`tab-${index}`}
                                    title={tab} // Browser tooltip for mobile view
                                >
                                    <i className={`${icons[index]} text-2xl`} />
                                </Tab>
                            ))}
                        </TabList>
                        <TabPanels className="flex-1 p-4">
                            {
                                this.props.children ?
                                    this.props.children.map((child: React.ReactNode, index: number) => (
                                        <TabPanel
                                            key={index}
                                            id={`panel-${index}`}
                                            className={clsx(
                                                'rounded-xl p-4',
                                                index === selectedIndex ? '' : 'hidden'
                                            )}
                                        >
                                            {child}
                                        </TabPanel>
                                    ))
                                    : null
                            }
                        </TabPanels>
                    </TabGroup>
                </div>
            </div>
        )
    }
}
