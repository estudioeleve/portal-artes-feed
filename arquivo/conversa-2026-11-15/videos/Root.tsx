import React from 'react';
import {Composition} from 'remotion';
import {SantaLuzia, SL_TOTAL} from './SantaLuzia';
import {DenunciaDemo, DENUNCIA_TOTAL} from './DenunciaDemo';
import {Fly, FLY_TOTAL} from './Fly';
import {Fly2, FLY2_TOTAL} from './Fly2';
import {FimReels, FIM_REELS_TOTAL} from './FimReels';
// Cada vídeo novo: crie src/<Nome>.tsx a partir do SantaLuzia.tsx (ou de exemplos/Nikolas.tsx) e registre aqui.
export const Root: React.FC = () => (<>
  <Composition id="SantaLuzia" component={SantaLuzia} durationInFrames={Math.round(SL_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="DenunciaDemo" component={DenunciaDemo} durationInFrames={Math.round(DENUNCIA_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="Fly" component={Fly} durationInFrames={Math.round(FLY_TOTAL * 60)} fps={60} width={1080} height={1920} />
  <Composition id="FlyPrev" component={Fly} durationInFrames={Math.round(FLY_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="Fly2" component={Fly2} durationInFrames={Math.round(FLY2_TOTAL * 60)} fps={60} width={1080} height={1920} />
  <Composition id="Fly2Prev" component={Fly2} durationInFrames={Math.round(FLY2_TOTAL * 30)} fps={30} width={1080} height={1920} />
  <Composition id="FimReels" component={FimReels} durationInFrames={Math.round(FIM_REELS_TOTAL * 30)} fps={30} width={1080} height={1920} />
</>);
