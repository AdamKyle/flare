import React from 'react';
import ContainerWithTitle from "ui/container/container-with-title";
import Card from "ui/cards/card";
import DonationsProps from "./types/donations-props";

const Donations = ({ on_close }: DonationsProps) => {
  return (
    <ContainerWithTitle manageSectionVisibility={on_close} title={'Tlessa needs your help'}>
      <Card>
        Content
      </Card>
    </ContainerWithTitle>
  )
};

export default Donations;