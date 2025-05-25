import React, { useEffect, useState } from 'react';

import CoordinatesDropDown from './coordinates-drop-down';
import CoordinatesProps from './types/coordinates-props';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const Coordinates = ({
  coordinates,
  x,
  y,
  on_select_coordinates,
}: CoordinatesProps) => {
  const [selectedCoordinates, setSelectedCoordinates] = useState({
    x: x,
    y: y,
  });

  const [updateParent, setUpdateParent] = useState(false);

  const getPreSelectedCoordinate = (coordinate: number) => {
    return {
      label: coordinate.toString(),
      value: coordinate,
    };
  };

  const onSelectXCoordinate = (selectedValue: DropdownItem) => {
    console.log('Here - onSelectXCoordinate');
    setSelectedCoordinates((prev) => ({
      x: parseInt(selectedValue.value as string, 10),
      y: prev.y,
    }));

    setUpdateParent((prevValue) => !prevValue);
  };

  const onSelectYCoordinate = (selectedValue: DropdownItem) => {
    setSelectedCoordinates((prev) => ({
      x: prev.x,
      y: parseInt(selectedValue.value as string, 10),
    }));

    setUpdateParent((prevValue) => !prevValue);
  };

  useEffect(
    () => {
      on_select_coordinates(selectedCoordinates);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [updateParent]
  );

  useEffect(() => {
    setSelectedCoordinates({
      x: x,
      y: y,
    });
  }, [x, y]);

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          X Coordinate
        </label>
        <CoordinatesDropDown
          coordinates={coordinates.x}
          default_position={getPreSelectedCoordinate(selectedCoordinates.x)}
          on_select={onSelectXCoordinate}
        />
      </div>
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Y Coordinate
        </label>
        <CoordinatesDropDown
          coordinates={coordinates.y}
          default_position={getPreSelectedCoordinate(selectedCoordinates.y)}
          on_select={onSelectYCoordinate}
        />
      </div>
    </div>
  );
};

export default Coordinates;
