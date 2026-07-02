const basePodiumCardClasses =
    "flex h-full min-h-[330px] flex-col items-stretch justify-between rounded-sm border-2 p-6 text-center";
const featuredPodiumCardClasses = "min-h-[380px] shadow-md lg:p-8";
const goldPodiumCardClasses =
    "bg-gradient-to-br from-gold-200 to-gold-300 border-gold-600 text-gold-950 ring-2 ring-gold-500 shadow-md dark:from-gold-950 dark:to-gold-900 dark:border-gold-400 dark:text-gold-100 dark:ring-gold-700";
const silverPodiumCardClasses =
    "bg-gradient-to-br from-silver-200 to-silver-300 border-silver-500 text-silver-950 ring-2 ring-silver-400 shadow-sm dark:from-silver-950 dark:to-silver-900 dark:border-silver-300 dark:text-silver-100 dark:ring-silver-700";
const bronzePodiumCardClasses =
    "bg-gradient-to-br from-ochre-200 to-ochre-300 border-ochre-600 text-ochre-950 ring-2 ring-ochre-500 shadow-sm dark:from-ochre-950 dark:to-ochre-900 dark:border-ochre-400 dark:text-ochre-100 dark:ring-ochre-700";
const neutralPodiumCardClasses =
    "border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100";

const goldAccentClasses = "text-gold-700 dark:text-gold-300";
const silverAccentClasses = "text-silver-700 dark:text-silver-300";
const bronzeAccentClasses = "text-ochre-700 dark:text-ochre-300";
const neutralAccentClasses = "text-gray-700 dark:text-gray-300";

const goldPillClasses =
    "bg-gold-100 border-gold-400 text-gold-700 dark:bg-gold-900 dark:border-gold-500 dark:text-gold-300";
const silverPillClasses =
    "bg-silver-100 border-silver-300 text-silver-700 dark:bg-silver-900 dark:border-silver-500 dark:text-silver-300";
const bronzePillClasses =
    "bg-ochre-100 border-ochre-400 text-ochre-700 dark:bg-ochre-900 dark:border-ochre-500 dark:text-ochre-300";
const neutralPillClasses =
    "bg-gray-50 border-gray-300 text-gray-800 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200";

const goldTableRowClasses =
    "bg-gold-100 dark:bg-gold-950 border-l-4 border-gold-600 dark:border-gold-400";
const silverTableRowClasses =
    "bg-silver-100 dark:bg-silver-950 border-l-4 border-silver-500 dark:border-silver-300";
const bronzeTableRowClasses =
    "bg-ochre-100 dark:bg-ochre-950 border-l-4 border-ochre-600 dark:border-ochre-400";
const neutralTableRowClasses =
    "bg-white border-l-4 border-transparent hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-900";

const goldRankBadgeClasses =
    "bg-gold-100 border-gold-500 text-gold-900 dark:bg-gold-900 dark:border-gold-400 dark:text-gold-100";
const silverRankBadgeClasses =
    "bg-silver-100 border-silver-300 text-silver-900 dark:bg-silver-900 dark:border-silver-400 dark:text-silver-100";
const bronzeRankBadgeClasses =
    "bg-ochre-100 border-ochre-500 text-ochre-900 dark:bg-ochre-900 dark:border-ochre-400 dark:text-ochre-100";
const neutralRankBadgeClasses =
    "bg-gray-50 border-gray-300 text-gray-800 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200";

const goldMobileCardClasses =
    "bg-gold-100 border-gold-600 text-gold-950 dark:bg-gold-950 dark:border-gold-400 dark:text-gold-100";
const silverMobileCardClasses =
    "bg-silver-100 border-silver-500 text-silver-950 dark:bg-silver-950 dark:border-silver-300 dark:text-silver-100";
const bronzeMobileCardClasses =
    "bg-ochre-100 border-ochre-600 text-ochre-950 dark:bg-ochre-950 dark:border-ochre-400 dark:text-ochre-100";
const neutralMobileCardClasses =
    "border-gray-200 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100";

const goldTableCellTextClasses = "text-gold-950 dark:text-gold-100";
const silverTableCellTextClasses = "text-silver-950 dark:text-silver-100";
const bronzeTableCellTextClasses = "text-ochre-950 dark:text-ochre-100";
const neutralTableCellTextClasses = "text-gray-900 dark:text-gray-100";

const goldTableCharacterLinkClasses =
    "text-gold-950 hover:text-gold-800 dark:text-gold-100 dark:hover:text-gold-300";
const silverTableCharacterLinkClasses =
    "text-silver-950 hover:text-silver-700 dark:text-silver-100 dark:hover:text-silver-300";
const bronzeTableCharacterLinkClasses =
    "text-ochre-950 hover:text-ochre-800 dark:text-ochre-100 dark:hover:text-ochre-300";
const neutralTableCharacterLinkClasses =
    "text-regent-st-blue-600 hover:text-regent-st-blue-700 dark:text-regent-st-blue-300 dark:hover:text-regent-st-blue-200";

export function podiumCardClasses(rank: number, isFeatured: boolean): string {
    if (rank === 1) {
        return (
            basePodiumCardClasses +
            " " +
            goldPodiumCardClasses +
            (isFeatured ? " " + featuredPodiumCardClasses : "")
        );
    }

    if (rank === 2) {
        return basePodiumCardClasses + " " + silverPodiumCardClasses;
    }

    if (rank === 3) {
        return basePodiumCardClasses + " " + bronzePodiumCardClasses;
    }

    return basePodiumCardClasses + " " + neutralPodiumCardClasses;
}

export function podiumAccentClasses(rank: number): string {
    if (rank === 1) {
        return goldAccentClasses;
    }

    if (rank === 2) {
        return silverAccentClasses;
    }

    if (rank === 3) {
        return bronzeAccentClasses;
    }

    return neutralAccentClasses;
}

export function podiumPillClasses(rank: number): string {
    if (rank === 1) {
        return goldPillClasses;
    }

    if (rank === 2) {
        return silverPillClasses;
    }

    if (rank === 3) {
        return bronzePillClasses;
    }

    return neutralPillClasses;
}

export function tableRowClasses(rank: number): string {
    if (rank === 1) {
        return goldTableRowClasses;
    }

    if (rank === 2) {
        return silverTableRowClasses;
    }

    if (rank === 3) {
        return bronzeTableRowClasses;
    }

    return neutralTableRowClasses;
}

export function rankBadgeClasses(rank: number): string {
    if (rank === 1) {
        return goldRankBadgeClasses;
    }

    if (rank === 2) {
        return silverRankBadgeClasses;
    }

    if (rank === 3) {
        return bronzeRankBadgeClasses;
    }

    return neutralRankBadgeClasses;
}

export function mobileCardClasses(rank: number): string {
    if (rank === 1) {
        return goldMobileCardClasses;
    }

    if (rank === 2) {
        return silverMobileCardClasses;
    }

    if (rank === 3) {
        return bronzeMobileCardClasses;
    }

    return neutralMobileCardClasses;
}

export function tableCellTextClasses(rank: number): string {
    if (rank === 1) {
        return goldTableCellTextClasses;
    }

    if (rank === 2) {
        return silverTableCellTextClasses;
    }

    if (rank === 3) {
        return bronzeTableCellTextClasses;
    }

    return neutralTableCellTextClasses;
}

export function tableCharacterLinkClasses(rank: number): string {
    if (rank === 1) {
        return goldTableCharacterLinkClasses;
    }

    if (rank === 2) {
        return silverTableCharacterLinkClasses;
    }

    if (rank === 3) {
        return bronzeTableCharacterLinkClasses;
    }

    return neutralTableCharacterLinkClasses;
}
