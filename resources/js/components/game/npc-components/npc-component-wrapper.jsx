import React from 'react';
import ConjureComponent from "./conjure-component";

export default class NpcComponentWrapper extends React.Component {

  constructor(props) {
    super(props);
  }

  getComponent() {
    switch(this.props.npcComponentName) {
      case 'Conjure':
        return <ConjureComponent closeComponent={this.props.close} />
    }
  }

  render() {
    return this.getComponent();
  }
}
