import React from 'react';
import BuildingQueue from './partials/building-queue';
import UnitQueue from './partials/unit-queue';

export default class QueueModal extends React.Component {

  constructor(props) {
    super(props)
  }

  render() {
    if (this.props.queueType === 'building') {
      return <BuildingQueue buildings={this.props.kingdom.buildings} queueData={this.props.queueData}
                            close={this.props.close} show={this.props.show}/>
    } else {
      return <UnitQueue kingdom={this.props.kingdom} units={this.props.kingdom.recruitable_units}
                        queueData={this.props.queueData} close={this.props.close} show={this.props.show}/>
    }
  }
}
