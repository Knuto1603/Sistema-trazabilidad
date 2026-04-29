#!/bin/sh
set -e

cat > /usr/share/nginx/html/env.js << EOF
window.__ENV = {
  securityUrl: '${SECURITY_URL:-/security/api}',
  coreUrl: '${CORE_URL:-/api}'
};
EOF

exec nginx -g 'daemon off;'
