module.exports = {
  apps: [
    {
      name: 'es4-solutions',
      script: 'artisan',
      args: 'octane:start --server=frankenphp --port=8000',
      interpreter: 'php',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
    },
  ]
};
