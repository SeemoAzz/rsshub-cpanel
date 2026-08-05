'use strict';

const path = require('node:path');
const { pathToFileURL } = require('node:url');

require('dotenv').config({ path: path.join(__dirname, '.env') });

process.env.NODE_ENV = process.env.NODE_ENV || 'production';
process.env.NODE_OPTIONS = `${process.env.NODE_OPTIONS || ''} --max-http-header-size=32768`.trim();
process.env.CACHE_TYPE = process.env.CACHE_TYPE || 'memory';
process.env.LISTEN_INADDR_ANY = process.env.LISTEN_INADDR_ANY || '1';
process.env.ENABLE_CLUSTER = process.env.ENABLE_CLUSTER || '0';

if (!process.env.PORT) {
  process.env.PORT = '1200';
}

const entry = pathToFileURL(path.join(__dirname, 'RSSHub', 'dist', 'index.mjs')).href;

import(entry).catch((err) => {
  console.error('RSSHub failed to start:', err);
  process.exit(1);
});
