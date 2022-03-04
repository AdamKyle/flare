import React from 'react';
import { Tab } from '@headlessui/react'
import GameProps from "./lib/game/types/GameProps";

function classNames(...classes: string[]) {
    return classes.filter(Boolean).join(' ')
}

export default class Game extends React.Component<GameProps, {}> {

    constructor(props: GameProps | Readonly<GameProps>) {
        super(props)
    }


    render() {
        return(
            <div className="md:container">
                <Tab.Group>
                    <Tab.List className="w-full md:w-1/3 grid grid-cols-3 gap-2 content-center">
                        <Tab key={'game'}
                             className={({selected}) => classNames(
                                 'w-full py-2.5 text-sm font-medium rounded-sm',
                                 'focus:outline-none focus:ring-2 ring-offset-2 ring-offset-neutral-400 ring-white ring-opacity-60',
                                 selected
                                     ? 'bg-neutral-500 shadow text-slate-800'
                                     : 'text-slate-200 hover:bg-white/[0.12] hover:text-white bg-gray-800'
                             )}
                        >Game</Tab>
                        <Tab key={'character-sheet'}
                             className={({selected}) => classNames(
                                 'w-full py-2.5 text-sm font-medium rounded-sm',
                                 'focus:outline-none focus:ring-2 ring-offset-2 ring-offset-neutral-400 ring-white ring-opacity-60',
                                 selected
                                     ? 'bg-neutral-500 shadow text-slate-800'
                                     : 'text-slate-200 hover:bg-white/[0.12] hover:text-white bg-gray-800'
                             )}
                        >Character Sheet</Tab>
                        <Tab key={'kingdom-management'}
                             className={({selected}) => classNames(
                                 'w-full py-2.5 text-sm font-medium rounded-sm',
                                 'focus:outline-none focus:ring-2 ring-offset-2 ring-offset-neutral-400 ring-white ring-opacity-60',
                                 selected
                                     ? 'bg-neutral-500 shadow text-slate-800'
                                     : 'text-slate-200 hover:bg-white/[0.12] hover:text-white bg-gray-800'
                             )}
                        >Kingdoms</Tab>
                    </Tab.List>
                    <Tab.Panels className="mt-5">
                        <Tab.Panel
                            key={'game-panel'}
                        >
                            <div className="grid lg:grid-cols-3 gap-3">
                                <div className="w-full col-span-2">
                                    <div className="bg-gray-800 w-full text-center mb-10">
                                        <p>Character info</p>
                                    </div>
                                    <div className="bg-gray-800 w-full text-center">
                                        <p>Actions Section</p>
                                    </div>
                                </div>
                                <div className="bg-gray-800 col-start-1 col-end-3 mt-5 md:mt-0 md:col-start-3 md:col-end-3">
                                    Map Area
                                </div>
                            </div>
                        </Tab.Panel>
                        <Tab.Panel
                            key={'character-panel'}
                        >Content 2</Tab.Panel>
                        <Tab.Panel
                            key={'kingdom-panel'}
                        >Content 3</Tab.Panel>
                    </Tab.Panels>
                </Tab.Group>

                <div className="bg-gray-800 w-full text-center mt-10">
                    <p>Chat Section</p>
                </div>
            </div>
        )
    }
}
