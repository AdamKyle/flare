import { LocationInfoTypes } from '../../enums/location-info-types';

export default interface CorruptedLocationProps {
  is_corrupted: boolean;
  handle_on_info_click: (infoType: LocationInfoTypes) => void;
}
