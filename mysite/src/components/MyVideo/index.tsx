import React, { useEffect, useRef } from 'react';
import videojs from 'video.js';
import 'video.js/dist/video-js.css';
import browser from '@/utils/browser';

export type MyVideoProps = {
  options: any;
  onReady?: (play: any) => void;
};

const MyVideo = React.forwardRef((props: MyVideoProps, ref) => {
  const videoRef = useRef(null);
  const playerRef = useRef(null);
  const { options, onReady } = props;

  useEffect(() => {
    if (!playerRef.current) {
      const videoElement = videoRef.current;
      if (!videoElement) return;

      const player = (playerRef.current = videojs(videoElement, options, () => {
        onReady && onReady(player);
      }));
    } else {
      const player = playerRef.current;
      player.src(options.sources[0].src);
      player.autoplay(true);
    }
  }, [options, videoRef]);

  return (
    <div data-vjs-player>
      <video
        width={browser.mobile() ? '100%' : props?.options?.width || '1020px'}
        height={browser.mobile() ? 'auto' : props?.options?.height || '720px'}
        style={{
          margin: '0 auto',
        }}
        ref={videoRef}
        className="video-js vjs-big-play-centered"
      />
    </div>
  );
});

export default MyVideo;
