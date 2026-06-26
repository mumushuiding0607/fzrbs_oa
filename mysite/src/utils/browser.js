export default {
  mobile() {
    let ua = navigator.userAgent;
    let isWindowsPhone = /(?:Windows Phone)/.test(ua);
    let isSymbian = /(?:SymbianOS)/.test(ua);
    let isAndroid = /(?:Android)/.test(ua);
    let isFireFox = /(?:Firefox)/.test(ua);
    let isTablet =
      /(?:iPad|PlayBook)/.test(ua) ||
      (isAndroid && !/(?:Mobile)/.test(ua)) ||
      (isFireFox && /(?:Tablet)/.test(ua));
    let isPhone = /(?:iPhone)/.test(ua) && !isTablet;
    let isChrome = /(?:Chrome|CriOS)/.test(ua);
    let isMobile = isWindowsPhone || isSymbian || isAndroid || isPhone;
    return isMobile ? true : false;
  },
};
