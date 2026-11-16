import React from 'react';
import {Composition} from 'remotion';
import {FimPortal, FIM_TOTAL} from './FimPortal';
export const Root: React.FC = () => (<>
  <Composition id="FimLuziense" component={FimPortal as any} defaultProps={{tema: 'luziense'}} durationInFrames={Math.round(FIM_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="FimPolitica" component={FimPortal as any} defaultProps={{tema: 'politica'}} durationInFrames={Math.round(FIM_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="FimComercio" component={FimPortal as any} defaultProps={{tema: 'comercio'}} durationInFrames={Math.round(FIM_TOTAL * 30)} fps={30} width={1080} height={1920} />
</>);
