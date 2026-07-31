import React from "react";
import { createPortal } from "react-dom";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileItemDetailModalProps from "../../../types/profile/equipment/profile-item-detail-modal-props";

export default class ProfileItemDetailModal extends React.Component<ProfileItemDetailModalProps> {
    renderRow(label: string, value: TopsValue | undefined) {
        return (
            <div>
                <p className="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {label}
                </p>
                <p className="font-semibold text-gray-900 dark:text-gray-100">
                    {formatTopsValue(value)}
                </p>
            </div>
        );
    }

    renderModal() {
        const statModifiers = asTopsRecord(this.props.item.stat_modifiers);
        const sockets = asTopsRecordList(this.props.item.sockets);
        const prefix = asTopsRecord(this.props.item.prefix);
        const suffix = asTopsRecord(this.props.item.suffix);
        const itemSkill = asTopsRecord(this.props.item.item_skill);

        return (
            <div
                className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="profile-item-title"
            >
                <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-sm bg-white p-6 shadow-xl dark:bg-gray-800">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2
                                id="profile-item-title"
                                className="text-xl font-bold text-gray-900 dark:text-gray-100"
                            >
                                {this.props.item.item_name ??
                                    this.props.item.name ??
                                    "Item"}
                            </h2>
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                {this.props.item.type ?? "Unknown Type"}
                            </p>
                        </div>
                        <button
                            type="button"
                            className="rounded-sm border border-gray-300 px-3 py-2 text-sm font-semibold dark:border-gray-600"
                            onClick={this.props.onClose}
                        >
                            Close
                        </button>
                    </div>

                    <div className="mt-5 grid gap-4 sm:grid-cols-3">
                        {this.renderRow("Attack", this.props.item.attack)}
                        {this.renderRow("Healing", this.props.item.healing)}
                        {this.renderRow("AC", this.props.item.ac)}
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        {Object.keys(statModifiers).map((stat: string) =>
                            this.renderRow(
                                stat.toUpperCase(),
                                statModifiers[stat],
                            ),
                        )}
                    </div>

                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        {this.renderRow("Prefix", prefix.name)}
                        {this.renderRow("Suffix", suffix.name)}
                        {this.renderRow(
                            "Holy Stacks",
                            this.props.item.holy_stacks,
                        )}
                        {this.renderRow("Item Skill", itemSkill.name)}
                    </div>

                    <div className="mt-5">
                        <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                            Sockets / Gems
                        </h3>
                        <div className="mt-2 grid gap-2">
                            {sockets.length === 0 ? (
                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                    No socketed gems.
                                </p>
                            ) : (
                                sockets.map(
                                    (
                                        socket: Record<string, TopsValue>,
                                        index: number,
                                    ) => (
                                        <div
                                            key={index}
                                            className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                                        >
                                            {formatTopsValue(socket.gem_name)}
                                        </div>
                                    ),
                                )
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    render() {
        return createPortal(this.renderModal(), document.body);
    }
}
