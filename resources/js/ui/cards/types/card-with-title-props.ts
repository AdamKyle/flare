import { TitleSize } from "../enums/title-size";
import CardProps from "./card-props";

export default interface CardWithTitleProps extends CardProps {
    title: string;
    title_size?: TitleSize;
}
