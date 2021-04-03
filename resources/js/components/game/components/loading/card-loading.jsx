import React from 'react';
import ContentLoader from 'react-content-loader';
import Card from '../templates/card';

export default class CardLoading extends React.Component {

  render() {
    return (
      <Card>
        <ContentLoader viewBox="0 0 380 300">
          {/* Only SVG shapes */}
          <rect x="0" y="0" rx="4" ry="4" width="500" height="230"/>
          <rect x="0" y="245" rx="3" ry="3" width="250" height="10"/>
          <rect x="0" y="265" rx="3" ry="3" width="250" height="10"/>
          <rect x="0" y="285" rx="3" ry="3" width="250" height="10"/>
        </ContentLoader>
      </Card>
    );
  }
}
