import CardProps from "./card-props";
import { TitleSize } from "../enums/title-size";

export default interface CardWithTitleProps extends CardProps {
    title: string;
    title_size?: TitleSize;
}
