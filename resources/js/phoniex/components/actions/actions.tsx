import React from "react";
import Card from "../../ui/cards/card";
import LinkButton from "../../ui/buttons/link-button";
import { ButtonVariant } from "../../ui/buttons/enums/button-variant-enum";
import Button from "../../ui/buttons/button";
import GradientButton from "../../ui/buttons/gradient-button";
import { ButtonGradientVarient } from "../../ui/buttons/enums/button-gradient-variant";
import Seperator from "../../ui/seperatror/seperator";
import IconButton from "../../ui/buttons/icon-button";

export default class Actions extends React.Component {
    render() {
        return (
            <Card>
                <div className="w-full flex flex-col lg:flex-row">
                    {/* Icon Container */}
                    <div className="flex lg:flex-col items-center mx-auto w-2/3 lg:w-10 justify-between lg:items-start lg:mr-4 lg:justify-start lg:mt-3 mt-4 space-y-0 lg:space-y-2">
                        <div className="flex lg:flex-col w-full lg:w-auto lg:space-y-2 space-x-2 lg:space-x-0 ">
                            <IconButton
                                label="Character"
                                icon={
                                    <i
                                        className="fas fa-heart text-sm"
                                        aria-hidden="true"
                                    ></i>
                                }
                                variant={ButtonVariant.PRIMARY}
                                on_click={() => {}}
                                additional_css="w-full lg:w-auto"
                            />
                            <IconButton
                                label="Map"
                                icon={
                                    <i
                                        className="fas fa-shield-alt text-sm"
                                        aria-hidden="true"
                                    ></i>
                                }
                                variant={ButtonVariant.PRIMARY}
                                on_click={() => {}}
                                additional_css="w-full lg:w-auto"
                            />
                            <IconButton
                                label="Craft"
                                icon={
                                    <i
                                        className="fas fa-magic text-sm"
                                        aria-hidden="true"
                                    ></i>
                                }
                                variant={ButtonVariant.PRIMARY}
                                on_click={() => {}}
                                additional_css="w-full lg:w-auto"
                            />

                            <div className="hidden lg:block absolute top-8 left-36 ml-4 shadow-lg rounded-sm border border-gray-500 dark:border-gray-700 w-[28rem] z-10 max-w-none text-black dark:text-gray-300">
                                <div className="bg-gray-400 dark:bg-gray-700 border-b-2 border-b-gray-500 dark:border-b-gray-600 px-4 py-3 flex items-center justify-between">
                                    <h3 className="text-lg font-semibold mb-0">
                                        Basic Card
                                    </h3>
                                    <button
                                        className="p-0 bg-transparent border-none cursor-pointer"
                                        onClick={() => {
                                            console.log("Close button clicked");
                                        }}
                                        aria-label="Close"
                                    >
                                        <i
                                            className="fas fa-times-circle text-rose-600 dark:text-rose-500 rounded-full text-lg p-1"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                </div>
                                <div className="p-4 bg-gray-200 dark:bg-gray-600">
                                    <p>
                                        This is a simple card content area. It
                                        will grow as needed.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Content Area */}
                    <div className="flex flex-col items-center lg:items-start w-full">
                        <img
                            src="https://placecats.com/250/250"
                            alt="A cute cat"
                            className="
                                mx-auto mt-4 rounded-md drop-shadow-md
                                sm:w-64 lg:w-72 lg:w-80 xl:w-96
                                transition-all duration-300 ease-in-out transform hover:scale-105
                                dark:drop-shadow-lg dark:border dark:border-gray-700 dark:bg-gray-800
                                focus:outline-none focus:ring-2 focus:ring-danube-500 focus:ring-offset-2 focus:ring-offset-white
                            "
                        />
                        <div
                            className="
                                mx-auto mt-4 flex items-center justify-center
                                w-full lg:w-1/3 gap-x-3 text-lg leading-none
                            "
                        >
                            <i
                                className="fas fa-chevron-circle-left text-xl"
                                aria-hidden="true"
                            ></i>
                            <span className="font-semibold">Monster Name</span>
                            <i
                                className="fas fa-chevron-circle-right text-xl"
                                aria-hidden="true"
                            ></i>
                        </div>
                        <div
                            className="
                                mx-auto mt-4 flex items-center justify-center
                                w-full lg:w-1/3 gap-x-3 text-lg leading-none
                            "
                        >
                            <LinkButton
                                label="View Stats"
                                variant={ButtonVariant.PRIMARY}
                                on_click={() => {}}
                            />
                        </div>
                        <div
                            className="
                                w-full lg:w-2/3 mx-auto mt-4 flex items-center justify-center
                                gap-x-3 text-lg leading-none
                            "
                        >
                            <div className="w-full lg:w-1/3">
                                <div className="space-y-2 mb-4">
                                    <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
                                        <span
                                            id="character-name"
                                            className="sr-only"
                                        >
                                            Monster Name
                                        </span>
                                        <span>Monster Name</span>
                                        <span
                                            aria-labelledby="character-name"
                                            aria-live="polite"
                                        >
                                            100/100
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-rose-600 dark:bg-rose-500 rounded-full h-full"
                                            style={{ width: "100%" }}
                                            role="progressbar"
                                            aria-valuenow={100}
                                            aria-valuemin={0}
                                            aria-valuemax={100}
                                        ></div>
                                    </div>
                                </div>
                                <div className="space-y-2 mb-4">
                                    <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
                                        <span
                                            id="character-name"
                                            className="sr-only"
                                        >
                                            Character Name
                                        </span>
                                        <span>Character Name</span>
                                        <span
                                            aria-labelledby="character-name"
                                            aria-live="polite"
                                        >
                                            100/100
                                        </span>
                                    </div>
                                    <div className="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            className="bg-emerald-600 dark:bg-emerald-500 rounded-full h-full"
                                            style={{ width: "100%" }}
                                            role="progressbar"
                                            aria-valuenow={100}
                                            aria-valuemin={0}
                                            aria-valuemax={100}
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="mx-auto mt-4 flex flex-col sm:flex-row items-center justify-center w-full lg:w-1/3 gap-y-4 gap-x-3 text-lg leading-none">
                            <Button
                                label="Attack"
                                variant={ButtonVariant.PRIMARY}
                                additional_css="w-full lg:w-1/3"
                                on_click={() => {}}
                            />
                            <Button
                                label="Cast"
                                variant={ButtonVariant.PRIMARY}
                                additional_css="w-full lg:w-1/3"
                                on_click={() => {}}
                            />
                        </div>

                        <div className="mx-auto mt-4 flex flex-col sm:flex-row items-center justify-center w-full lg:w-1/3 gap-y-4 gap-x-3 text-lg leading-none">
                            <GradientButton
                                label="Atk & Cast"
                                gradient={
                                    ButtonGradientVarient.DANGER_TO_PRIMARY
                                }
                                additional_css="w-full lg:w-1/3"
                                on_click={() => {}}
                            />
                            <GradientButton
                                label="Cast & Atk"
                                gradient={
                                    ButtonGradientVarient.PRIMARY_TO_DANGER
                                }
                                additional_css="w-full lg:w-1/3"
                                on_click={() => {}}
                            />
                        </div>

                        <div className="mx-auto mt-4 flex flex-col sm:flex-row items-center justify-center w-full lg:w-1/3 gap-y-4 gap-x-3 text-lg leading-none">
                            <Button
                                label="Defend"
                                variant={ButtonVariant.PRIMARY}
                                additional_css="w-full lg:w-1/3"
                                on_click={() => {}}
                            />
                        </div>

                        <Seperator additional_css="w-full lg:w-1/5 mx-auto my-6" />
                        <div
                            className="
                                mx-auto mt-4 flex items-center justify-center
                                w-full lg:w-2/3 gap-x-3 text-lg leading-none
                            "
                        >
                            <div className="w-full lg:w-1/2 text-center italic space-y-2">
                                <div className="text-emerald-700 dark:text-emerald-500">
                                    You hit for 5,000 Damage!
                                </div>
                                <div className="text-danube-700 dark:text-danube-500">
                                    Your enchantments glow with rage!
                                </div>
                                <div className="text-emerald-700 dark:text-emerald-500">
                                    You hit for 5,000 Enchanted Damage!
                                </div>
                                <div className="text-rose-700 dark:text-rose-500">
                                    Your enemy hits you for 50,000 Damage!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        );
    }
}
