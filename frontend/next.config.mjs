const laravelOrigin = (process.env.LARAVEL_API_ORIGIN || "http://127.0.0.1:8000").replace(/\/+$/, "");

/** @type {import('next').NextConfig} */
const nextConfig = {
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: `${laravelOrigin}/api/:path*`,
      },
      {
        source: '/storage/:path*',
        destination: `${laravelOrigin}/storage/:path*`,
      },
    ];
  },
};

export default nextConfig;
