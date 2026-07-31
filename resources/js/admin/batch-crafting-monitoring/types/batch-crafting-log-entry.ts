export default interface BatchCraftingLogEntry {
    date: string;
    env: string;
    level: string;
    level_class: string;
    level_img: string;
    text: string;
    header: string[];
    context: string[];
    stack: string;
    in_file: string;
}
