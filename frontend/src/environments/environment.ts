declare global {
  interface Window {
    __ENV?: { securityUrl: string; coreUrl: string };
  }
}

export const environment = {
  production: true,
  securityUrl: window.__ENV?.securityUrl ?? '/security/api',
  coreUrl: window.__ENV?.coreUrl ?? '/api',
};
