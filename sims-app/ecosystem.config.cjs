module.exports = {
  apps: [
    {
      name: 'sims-queue',
      script: 'artisan',
      args: 'queue:work --tries=3 --timeout=90',
      interpreter: 'php',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        APP_ENV: 'production'
      }
    },
    {
      name: 'sims-scheduler',
      script: 'artisan',
      args: 'schedule:work',
      interpreter: 'php',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '128M',
      env: {
        APP_ENV: 'production'
      }
    },
    {
      name: 'whatsapp-service',
      script: '../whatsapp-service/server.js',
      cwd: '../whatsapp-service',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'production'
      }
    }
  ]
};
